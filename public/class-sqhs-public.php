<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       biir.dk
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Sqhs
 * @subpackage Sqhs/public
 * @author     ASi <asi@biir.dk>
 */
class Sqhs_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private $fp, $quiz_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of the plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->sqhs_add_shortcode();

	}


	function welcome_quiz( $atts ) {
		$atts = shortcode_atts( [ 'set' => '0' ], $atts, 'SQHS' );
		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'partials/sqhs-quiz-welcome.php';

		return ob_get_clean();
	}


	public function quiz_begin() {

		check_ajax_referer();

		global $wpdb;
		$set_ids = $_REQUEST['set'];

		$this->set_fingerprint();
		$fp = $this->fp;

		// check if last symbol in $set_ids is comma
		if ( substr( $set_ids, ( strlen( $set_ids ) - 1 ), 1 ) == ',' ) {
			$set_ids = substr( $set_ids, 0, ( strlen( $set_ids ) - 1 ) );
		}

		$set_arr = explode( ',', $set_ids );
		if ( empty( $set_arr ) || empty( $fp ) ) {
			wp_send_json( 'Not enough parameters for preparing a quiz', 412 );
		}

		/** @todo Check for unfinished quiz */
		/** @todo Check for time out */

		$set_full = $set_quiz = $quiz_questions = [];
		$sql = 'SELECT S.set_id, Q.question_id, N.max_question_quantity
					FROM ' . $wpdb->prefix . 'sqhs_relationships Q 
				    INNER JOIN ' . $wpdb->prefix . 'sqhs_relationships S ON S.category_id=Q.category_id
				    LEFT JOIN ' . $wpdb->prefix . 'sqhs_sets N ON N.id=S.set_id
				    WHERE S.set_id IN (' . $set_ids . ')
				        AND S.question_id is NULL
				        AND Q.set_id is NULL';
		$detail = $wpdb->get_results( $sql );

		foreach ( $set_arr as $id ) {
			// Fill array $set_full with all questions exist in database
			foreach ( $detail as $item ) {
				if ( $id == $item->set_id ) {
					$set_full[ $id ][] = [
						'question_id' => $item->question_id,
						'max_question_quantity' => $item->max_question_quantity
					];
				}
			}
			// Fill array $set_quiz with unique questions
			$set_full_items = count( $set_full[ $id ] ) - 1;
			while ( count( $set_quiz[ $id ] ) < $set_full[ $id ][0]['max_question_quantity'] ) {
				$sql = $set_full[ $id ][ rand( 0, $set_full_items ) ]['question_id'];
				if ( ! in_array( $sql, $set_quiz[ $id ] ) ) {
					$set_quiz[ $id ][] = $sql;
					$quiz_questions[]  = $sql;
				}
			}
		}

		$start_time = time();
		// Save new Quiz into DB
		$wpdb->insert( $wpdb->prefix . 'sqhs_quiz', [
			'fingerprint' => $fp,
			'start_time' => $start_time,
			'questions_ids' => json_encode( $quiz_questions )
		], [ '%s', '%d', '%s' ] );
		$quiz_id = $wpdb->insert_id;

		$sql = '';
		$n = 1; // Question number in log
		// Fill question's id to save to log
		foreach ( $quiz_questions as $question ) {
			$sql .= '(' . $quiz_id . ',' . $n . ',' . $question . ',' . $start_time . '),';
			$start_time = "NULL";
			$n = $n + 1;
		}
		$sql = substr( $sql, 0, ( strlen( $sql ) - 1 ) );

		// Save empty log of answers
		$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'sqhs_log (quiz_id, number, question_id, start_time) VALUES ' . $sql );

		$question = [
			'id' => $quiz_questions[0],
			'number' => '1',
			'total' => count( $quiz_questions ),
			'text' => $wpdb->get_var( 'SELECT text FROM ' . $wpdb->prefix . 'sqhs_questions WHERE id=' . $quiz_questions[0] )
		];

		$answers = $this->get_answers( $quiz_questions[0] );


		wp_send_json( [
			'quiz' => $quiz_id,
			'question' => $question,
			'answers' => $answers,
			'correct' => [],
			'action' => 'sqhs_questions_controller'
		], 201 );


	}


	public function questions_controller() {
		check_ajax_referer();

		$time = time();
		$this->set_fingerprint();
		$this->set_quiz_id();
		$fp = $this->fp;
		$quiz_id = $this->quiz_id;
		if ( ! $quiz_id ) {
			wp_send_json( 'Quiz id error', 412 );
		}
		if ( ! $fp ) {
			wp_send_json( 'Quiz identification error', 412 );
		}
		global $wpdb;

		// Total questions in the quiz
		$sql = 'SELECT COUNT(L.quiz_id) FROM ' . $wpdb->prefix . 'sqhs_quiz Q LEFT JOIN ' . $wpdb->prefix . 'sqhs_log L ON L.quiz_id=Q.id
				WHERE Q.id=' . $quiz_id . ' AND Q.fingerprint="' . $fp . '" AND Q.start_time>1570000000 AND Q.end_time IS NULL AND Q.result IS NULL';
		$total = $wpdb->get_var( $sql );

		// Investigate the Total value
		if ( !$total || $total < 1 ) {
			$sql = 'SELECT COUNT(L.quiz_id) FROM ' . $wpdb->prefix . 'sqhs_quiz Q 
				LEFT JOIN ' . $wpdb->prefix . 'sqhs_log L ON L.quiz_id=Q.id
					AND L.start_time >1570000000
					AND L.end_time>1570000000
					AND L.result IS NOT NULL
				WHERE Q.id=' . $quiz_id . ' 
					AND Q.fingerprint="' . $fp . '" 
					AND Q.start_time>1570000000 
					AND Q.end_time >1570000000 
					AND Q.result IS NOT NULL
					AND Q.present IS NULL
					AND Q.email IS NULL';
			$finish_questions = $wpdb->get_var( $sql );
			$sql = 'SELECT COUNT(L.quiz_id) FROM ' . $wpdb->prefix . 'sqhs_quiz Q 
				LEFT JOIN ' . $wpdb->prefix . 'sqhs_log L ON L.quiz_id=Q.id
					AND L.start_time >1570000000
					AND L.end_time>1570000000
					AND L.result IS NOT NULL
				WHERE Q.id=' . $quiz_id . ' 
					AND Q.fingerprint="' . $fp . '" 
					AND Q.start_time>1570000000 
					AND Q.end_time >1570000000 
					AND Q.result IS NOT NULL
					AND Q.present IS NULL
					AND Q.email IS NOT NULL';
			$finish_anketa = $wpdb->get_var( $sql );
			$sql = 'SELECT COUNT(L.quiz_id) FROM ' . $wpdb->prefix . 'sqhs_quiz Q 
				LEFT JOIN ' . $wpdb->prefix . 'sqhs_log L ON L.quiz_id=Q.id
					AND L.start_time >1570000000
					AND L.end_time>1570000000
					AND L.result IS NOT NULL
				WHERE Q.id=' . $quiz_id . ' 
					AND Q.fingerprint="' . $fp . '" 
					AND Q.start_time>1570000000 
					AND Q.end_time >1570000000 
					AND Q.result IS NOT NULL
					AND Q.present IS NOT NULL
					AND Q.email IS NOT NULL';
			$finish_quiz = $wpdb->get_var( $sql );

			if ( $finish_questions == 0 && $finish_anketa == 0 && $finish_quiz == 0) {
				// No actual quiz
				wp_send_json( 'No actual quiz', 404 );
			}
			if ( $finish_questions > 0 && $finish_anketa == 0 && $finish_quiz == 0) {
				// Quiz finished but anketa and priz don't showed
				$this->send_anketa();
			}
			if ( $finish_questions > 0 && $finish_anketa > 0 && $finish_quiz == 0) {
				// Quiz finished, anketa saved but priz don't showed
			}

		}


		// Is the opened question exists
		$sql = 'SELECT L.id, L.question_id, L.number, E.explanation FROM ' . $wpdb->prefix . 'sqhs_log L
          RIGHT JOIN ' . $wpdb->prefix . 'sqhs_quiz Q ON Q.id = ' . $quiz_id . '
          LEFT JOIN ' . $wpdb->prefix . 'sqhs_questions E ON E.id = L.question_id
            AND Q.fingerprint = "' . $fp . '"
            AND Q.start_time>1570000000
            AND Q.end_time IS NULL
            AND Q.result IS NULL
          WHERE L.quiz_id = ' . $quiz_id . '
            AND L.start_time>1570000000
            AND L.end_time IS NULL
            AND L.result IS NULL';
		$opened = $wpdb->get_row( $sql );
		if ( $opened ) {
			// Is answer skipped
			if ( ! isset( $_REQUEST['answer'] ) || ( isset( $_REQUEST['answer'] ) && empty( $_REQUEST['answer'] ) ) ) {
				// Answers skipped
				$sql = 2;
			} else {
				// Compare answers:
				$correct_answers = $wpdb->get_col( 'SELECT id FROM ' . $wpdb->prefix . 'sqhs_answers WHERE correct=1 AND question_id=' . $opened->question_id );
				if ( is_array( $_REQUEST['answer'] ) && empty( array_diff( $correct_answers, $_REQUEST['answer'] ) ) ) {
					// Answers are correct
					$sql = 1;
				} else {
					// Answers are INcorrect
					$sql = 0;
				}
			}
			// Save result and fill end_time
			$wpdb->update( $wpdb->prefix . 'sqhs_log', [
				'end_time' => $time,
				'result'   => $sql
			], [ 'id' => $opened->id ], [ '%d', '%d' ], [ '%d' ] );

			if ( $opened->explanation && strlen( $opened->explanation ) > 0 ) {
				// -> Prepare data for return explanation
				$question = [
					'id'     => $opened->question_id,
					'number' => $opened->number,
					'total'  => $total,
					'text'   => $opened->explanation
				];
				$correct  = $wpdb->get_col( 'SELECT correct FROM ' . $wpdb->prefix . 'sqhs_answers WHERE question_id=' . $opened->question_id );
				$answers  = $this->get_answers( $opened->question_id );
			} else {
				$new_question = $this->get_new_question( $quiz_id, $fp );
				if ( $new_question ) {
					// -> Prepare data for return newly started question
					$question = [
						'id'     => $new_question->question_id,
						'number' => $new_question->number,
						'total'  => $total,
						'text'   => $new_question->text
					];
					$correct  = [];
					$answers  = $this->get_answers( $new_question->question_id );
				}
			}

		} else {
			$new_question = $this->get_new_question( $quiz_id, $fp );
			if ( $new_question ) {
				// The quiz has unopened questions
				// -> Prepare data for return newly started question
				$question = [
					'id'     => $new_question->id,
					'number' => $new_question->number,
					'total'  => $total,
					'text'   => $new_question->text
				];
				$correct  = [];
				$answers  = $this->get_answers( $new_question->question_id );
			} else {

				// No free questions in a quiz left. Save quiz.end_time, quiz.result
				/*
				$opened = [];
				for ($i = 0; $i <= 2; $i++) {
					$sql = 'SELECT COUNT(C.result) FROM ' . $wpdb->prefix . 'sqhs_quiz Q
						  RIGHT JOIN ' . $wpdb->prefix . 'sqhs_log C ON C.quiz_id=Q.id AND C.result=' . $i . ' AND C.result IS NOT NULL
						WHERE Q.id=' . $quiz_id . '
						  AND Q.fingerprint="' . $fp . '"
						  AND Q.start_time>1570000000 AND Q.end_time IS NULL AND Q.result IS NULL';
					$opened[] = $wpdb->get_var( $sql );
				}
				list ( $answers_wrong, $answers_correct, $answers_skipped ) = $opened;
				*/
				$sql = 'SELECT COUNT(L.result) FROM ' . $wpdb->prefix . 'sqhs_log L
	              RIGHT JOIN ' . $wpdb->prefix . 'sqhs_quiz Q ON Q.id=L.quiz_id
	                AND Q.fingerprint = "' . $fp . '"
	                AND Q.start_time>1570000000
	                AND Q.end_time IS NULL
	                AND Q.result IS NULL
	              WHERE L.quiz_id = ' . $quiz_id . '
	                AND L.start_time>1570000000
	                AND L.end_time>1570000000
	                AND L.result =1';
				$sql = $wpdb->get_var( $sql ) / $total;
				$wpdb->update( $wpdb->prefix . 'sqhs_quiz', [
					'end_time' => $time,
					'result'   => $sql
				], [ 'id' => $quiz_id ], [ '%d', '%f' ], [ '%d' ] );

				$this->send_anketa();

			}
		}

		wp_send_json( [
			'quiz' => $quiz_id,
			'question' => $question,
			'answers' => $answers,
			'correct' => $correct,
			'action' => 'sqhs_questions_controller'
		], 200 );

	}


	public function anketa_handler() {

	}

	/**
	 * Send final anketa to frontend
	 */
	protected function send_anketa() {
		$anketa = [
			'header' => 'Один крок до подарунка!',
			'body' => 'Введіть ваш email:<br/><input type="email"/><br/>На якому ви курсі?',
			'question' => [ [ 'id' => '1', 'text' => '1'], [ 'id' => '2', 'text' => '2'], [ 'id' => '3', 'text' => '3'], [ 'id' => '4', 'text' => '4'], [ 'id' => '5', 'text' => '5'], [ 'id' => '100', 'text' => 'Закінчив'] ],
			'button' => 'РЕЗУЛЬТАТ ТЕСТУ'
		];

		wp_send_json( [
			'quiz' => $this->quiz_id,
			'anketa' => $anketa,
			'action' => 'sqhs_anketa_handler'
		], 200 );

	}


	/**
	 * Set quiz id from request
	 */
	private function set_quiz_id() {
		$quiz_id = ( isset( $_REQUEST['set'] ) ? sanitize_key( $_REQUEST['set'] ) : null );
		if ( ! is_numeric( $quiz_id ) || $quiz_id < 1 ) {
			$this->quiz_id = null;
		} else {
			$this->quiz_id = $quiz_id;
		}
	}

	/**
	 * Set fingerprint from request
	 */
	private function set_fingerprint() {
		$fp = ( isset( $_REQUEST['fingerprint'] ) ? sanitize_text_field( $_REQUEST['fingerprint'] ) : null );
		if ( ! $fp || strlen( $fp ) > 36 || strlen( $fp ) < 5 ) {
			$this->fp = null;
		} else {
			$this->fp = $fp;
		}
	}

	/**
	 * @param int $quiz_id Quiz id
	 * @param string $fp Fingerprint
	 *
	 * @return object|null Return first unstarted quiestion
	 */
	protected function get_new_question( $quiz_id, $fp ) {
		global $wpdb;

		// Get first unopened quiestion and open it
		$sql = 'SELECT L.id, L.question_id, L.number, T.text FROM ' . $wpdb->prefix . 'sqhs_log L
	          LEFT JOIN ' . $wpdb->prefix . 'sqhs_quiz Q ON Q.id = ' . $quiz_id . '
	          LEFT JOIN ' . $wpdb->prefix . 'sqhs_questions T ON T.id=L.question_id
	            AND Q.fingerprint = "' . $fp . '"
	            AND Q.start_time >1570000000
	            AND Q.end_time IS NULL
	            AND Q.result IS NULL
	          WHERE L.quiz_id = ' . $quiz_id . '
	            AND L.start_time IS NULL
	            AND L.end_time IS NULL
	            AND L.result IS NULL
			  ORDER BY L.id ASC
			  LIMIT 1';
		$free_question = $wpdb->get_row( $sql );
		if ( $free_question ) {
			// The quiz has unopened questions
			// Start new question
			$wpdb->update( $wpdb->prefix . 'sqhs_log', [ 'start_time' => time() ], [ 'id' => $free_question->id ], '%d', '%d' );
		}

		return $free_question;
	}

	/**
	 * @param $question_id
	 *
	 * @return object|null Return all answers for question
	 */
	public function get_answers( $question_id ) {
		global $wpdb;

		return $wpdb->get_results( 'SELECT id, text FROM ' . $wpdb->prefix . 'sqhs_answers WHERE question_id=' . $question_id );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sqhs_Loader as all of the hooks are defined in that particular class.
		 *
		 * The Sqhs_Loader will then create the relationship between the defined hooks
		 * and the functions defined in this class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sqhs-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sqhs_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sqhs_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sqhs-public.js', [ 'jquery' ], $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-fingerprint', plugin_dir_url( __FILE__ ) . 'js/fp210.js', [], null, true );
		//wp_enqueue_script( $this->plugin_name . '-react-dev', 'https://unpkg.com/react@16/umd/react.development.js' );
		//wp_enqueue_script( $this->plugin_name . '-react-dom-dev', 'https://unpkg.com/react-dom@16/umd/react-dom.development.js', $this->plugin_name . '-react-dev' );

		wp_localize_script( $this->plugin_name, 'wp_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce()
		) );

	}


	function sqhs_add_shortcode() {
		add_shortcode( 'SQHS', [ $this, 'welcome_quiz' ] );
	}


}
