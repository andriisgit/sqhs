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
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->sqhs_add_shortcode();

	}


    function welcome_quiz($atts) {
        $atts = shortcode_atts( ['set' => '0'], $atts, 'SQHS' );
	    ob_start();
        require_once plugin_dir_path(__FILE__) . 'partials/sqhs-quiz-welcome.php';
        return ob_get_clean();
    }


    public function quiz_begin() {

        check_ajax_referer();

        global $wpdb;
	    $set_ids = $_REQUEST['set'];
	    $fp = $_REQUEST['fingerprint'];

	    // check if last symbol in $set_ids is comma
	    if ( substr( $set_ids, (strlen($set_ids)-1), 1 ) == ',' )
	        $set_ids = substr( $set_ids, 0, (strlen($set_ids) - 1) );

	    $set_arr = explode( ',', $set_ids );
	    if ( empty($set_arr) || empty($fp) )
		    wp_send_json( [ 'status'=>'ERR', 'message'=>'Not enough parameters for preparing a quiz' ], 412 );

	    /** @todo Check for unfinished quiz */
	    /** @todo Check for time out */

	    $set_full = $set_quiz = $quiz_questions = [];
	    $sql = 'SELECT S.set_id, Q.question_id, N.max_question_quantity
					FROM ' . $wpdb->prefix.'sqhs_relationships Q 
				    INNER JOIN ' . $wpdb->prefix.'sqhs_relationships S ON S.category_id=Q.category_id
				    LEFT JOIN ' . $wpdb->prefix.'sqhs_sets N ON N.id=S.set_id
				    WHERE S.set_id IN (' . $set_ids . ')
				        AND S.question_id is NULL
				        AND Q.set_id is NULL';
	    $detail = $wpdb->get_results($sql);

	    foreach ( $set_arr as $id ) {
	    	// Fill array $set_full with all questions exist in database
	    	foreach ($detail as $item) {
			    if ( $id == $item->set_id ) {
				    $set_full[ $id ][] = [
					    'question_id'           => $item->question_id,
					    'max_question_quantity' => $item->max_question_quantity
				    ];
			    }
		    }
			// Fill array $set_quiz with unique questions
	    	$set_full_items = count($set_full[$id]) - 1;
		    while ( count($set_quiz[$id]) < $set_full[$id][0]['max_question_quantity'] ) {
			    $sql = $set_full[$id][rand(0, $set_full_items)]['question_id'];
			    if ( !in_array($sql, $set_quiz[$id]) ) {
				    $set_quiz[$id][] = $sql;
				    $quiz_questions[] = $sql;
			    }
		    }
	    }

	    $start_time = time();
	    // Save new Quiz into DB
	    $wpdb->insert( $wpdb->prefix.'sqhs_quiz', [
		    'fingerprint' => $fp,
		    'start_time' => $start_time,
		    'questions_ids' => json_encode($quiz_questions)
		    ], ['%s', '%d', '%s'] );
	    $quiz_id = $wpdb->insert_id;

	    $sql = '';
	    // Fill question's id to save to log
	    foreach ($quiz_questions as $question) {
	    	$sql .= '(' . $quiz_id . ',' . $question . ',' . $start_time . '),';
	    	$start_time = "NULL";
	    }
	    $sql = substr( $sql, 0, (strlen($sql) - 1) );

	    // Save empty log of answers
	    $wpdb->query('INSERT INTO ' . $wpdb->prefix.'sqhs_log (quiz_id, question_id, start_time) VALUES ' . $sql);

	    $question = [
	    	'id' => $quiz_questions[0],
	    	'number' => '1',
		    'total' => count($quiz_questions),
		    'text' => $this->get_question($quiz_questions[0])
	    ];

        wp_send_json( [ 'status'=>'OK', 'question'=>$question ], 201 );


    }


	public function get_question($id) {
		global $wpdb;
		return $wpdb->get_var( 'SELECT text FROM ' . $wpdb->prefix.'sqhs_questions WHERE id=' . $id );
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
		wp_enqueue_script( $this->plugin_name . '-fingerprint', plugin_dir_url( __FILE__ ) . 'js/fp210.js', [ ], null, true );
		//wp_enqueue_script( $this->plugin_name . '-react-dev', 'https://unpkg.com/react@16/umd/react.development.js' );
		//wp_enqueue_script( $this->plugin_name . '-react-dom-dev', 'https://unpkg.com/react-dom@16/umd/react-dom.development.js', $this->plugin_name . '-react-dev' );

        wp_localize_script( $this->plugin_name, 'wp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce()
        ) );

	}


	function sqhs_add_shortcode() {
	    add_shortcode('SQHS', [ $this, 'welcome_quiz' ]);
    }



}
