<?php


class Questions_List extends \WP_List_Table
{
    private $questions;     // array filled with Questions
    private $answers;       // array of Answers filled with answers for defined Question
    private $answers_block; // string contains html for output in sqhs-question-display.php
    private $answers_ids;   // string contains Answers ids for defined Question
    private $request;

    function __construct($request) {

        if ( !current_user_can('manage_categories') ) {
            $err_message = 'Access level error';
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-err-display.php';
            wp_die();
        }
        global $status, $page;
        $this->request = $request;

        parent::__construct( array(
            'singular'=> 'Question', //Singular label
            'plural' => 'Questions', //plural label, also this well be one of the table css class
            'ajax' => true //We won't support Ajax for this table
        ) );

        $this->check_action();

    }


    function get_columns() {
        $columns = array(
            'text' => 'Questions',
            'categories' => 'No of Categories consists in'
        );
        return $columns;
    }


    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->get_data();
        if ( $this->questions ) {
            usort( $this->questions, array(&$this, 'usort_reorder') );

            $per_page = 5;
            $current_page = $this->get_pagenum();
            $total_items = count($this->questions);
            $sliced_array = [];
            for ( $i = (($current_page - 1) * $per_page); $i < ((($current_page - 1) * $per_page) + $per_page); $i++ ) {
                if ( isset($this->questions[$i]) )
                    $sliced_array[] = $this->questions[$i];
            }
            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page
            ));
            $this->items = $sliced_array;
        }
    }


    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
            case 'text':
            case 'categories':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'text' => [ 'text', true ],
            'categories' => [ 'categories', false ]
        );
        return $sortable_columns;
    }


    function column_text($item) {
        $edit_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=edit&question=' . $item['id']), 'edit' );
        $del_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=delete&question=' . $item['id']), 'delete' );
        $actions = [
            'edit' => '<a href="' . $edit_url . '">' . _('Edit') . '</a>',
            'delete' => '<a href="' . $del_url . '" name="sqhs-going-delete">' . _('Delete') . '</a>',
        ];

        return sprintf( '%1$s %2$s', $item['text'], $this->row_actions($actions) );
    }


    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'text';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }


    /**
     * Loads question(s) into $questions
     *
     * @param array $params may contain [or 'text' or 'id']
     */
    private function get_data($params = null) {
        global $wpdb;
        $questions_table = $wpdb->prefix . 'sqhs_questions';
        $relations_table = $wpdb->prefix . 'sqhs_relationships';
        $where = '';
        if ( !empty($params['text']) )
            $where .= ' WHERE ' . $questions_table . '.text LIKE \'' . $params['text'] . '\' ';
        if ( !empty($params['id']) )
            $where == '' ? $where = ' WHERE ' . $questions_table . '.id=' . $params['id'] : $where .=  ' AND ' . $questions_table . '.id=' . $params['id'];

        $sql = 'SELECT ' . $questions_table . '.id, ' . $questions_table . '.text, ' . $questions_table . '.explanation, COUNT(' . $relations_table . '.id) AS \'categories\'';
        $sql .= ' FROM ' . $questions_table;
        $sql .= ' LEFT JOIN ' . $relations_table . ' ON ' . $relations_table . '.question_id=' . $questions_table . '.id';
        $sql .= $where;
        $sql .= ' GROUP BY ' . $questions_table . '.id';
        $sql .= ' ORDER BY ' . $questions_table . '.text';
        $this->questions = $wpdb->get_results($sql, ARRAY_A);
    }


    function no_items() {
        _e( 'No Quiz sets found.' );
    }


    /**
     * @param $request it is the $_REQUEST
     */
    private function check_action() {
        if ( isset($this->request['action']) || isset($this->request['question']) ) {

            require_once plugin_dir_path(__FILE__) . 'class-sqhs-categories.php';

            // Edit Question
            if ( wp_verify_nonce($this->request['_wpnonce'], 'edit') && is_numeric($this->request['question']) ) {

                $param['id'] = $this->request['question'];
                $this->get_data( $param );
                $this->get_answers_list($param['id']);

                $this->show_question($param['id']);

            }


            if ( wp_verify_nonce($this->request['_wpnonce'], 'delete') && isset($_REQUEST['question']) && is_numeric($_REQUEST['question']) ) {
	            $this->delete_question( sanitize_key( $_REQUEST['question'] ) );
	            print('<script type="text/javascript">window.location.href="' . wp_get_referer() . '"</script>');
	            exit;

            }

            // Add new Set
            if ( wp_verify_nonce($this->request['_wpnonce'], 'addnewquestion') ) {

                $this->show_question();

            }

            // Save question
            if ( wp_verify_nonce($this->request['_wpnonce'], 'savequestion') && $this->request['action'] == 'sqhs_questionsave') {
                
                $this->save_question();

            }
        }

    }


    /**
     * @param int $id Id of Set
     * @return array of category_id for Question
     */
    function get_relations($id) {
        global $wpdb;
        if ( empty($id) || !is_numeric($id))
            return false;

        $where = ' WHERE question_id=' . $id . ' AND category_id IS NOT NULL';
        $sql = 'SELECT category_id FROM ' . $wpdb->prefix . 'sqhs_relationships ' . $where;

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Loads (array)Answers into $answers
     * Loads (string)$answers_block
     * Loads (string)$answers_ids
     * 
     * @param int $id Id of Question
     */
    function get_answers_list($id) {
        if ( empty($id) || !is_numeric($id))
            return false;

        global $wpdb;
        $answers_block = $answers_ids = '';

        $sql = 'SELECT id, text, correct FROM ' . $wpdb->prefix . 'sqhs_answers WHERE question_id=' . $id;
        $this->answers = $wpdb->get_results($sql, ARRAY_A);

        if ( $this->answers ) {
            foreach ( $this->answers as $answer) {
                $answers_block .= '<div id="answer_' . $answer['id'] . '">';
                $answers_block .= '<a class="remove_answer" href="javascript:void(0);" data="' .$answer['id'] . '"><span class="dashicons dashicons-dismiss"></span></a>';
                $answers_block .= '<input type="text" required name="answer_text_' . $answer['id'] . '" placeholder="Hit answer here *" value="' . $answer['text'] . '" maxlength="49" class="regular-text"/>';
                $answers_block .= '<label><input ' . ($answer['correct'] == 1 ? 'checked="checked"' : '') . ' name="answer_correct_' . $answer['id'] . '" type="checkbox">Correct</label>';
                $answers_block .= '</div>';
                $answers_ids .= (string)$answer['id'] . ',';
            }
            $answers_ids = rtrim($answers_ids, ",");
        }

        $this->answers_block = $answers_block;
        $this->answers_ids = $answers_ids;
        
    }


    function show_question ($id = null) {
        $catlist = new Categories_List($this->request);

        if ( $id ) {
            $relationships = $this->get_relations($id);
            $relationships = array_column($relationships, 'category_id');
            $question = $this->questions[0];
        } else {
            $relationships = null;
        }

        $li = $catlist->get_categories_list($relationships);
        $question = $this->questions[0];
        $question['heading'] = 'Edit Question';
        $question['subheading'] = 'Question';

        $answers_block = $this->answers_block;
        $answers_ids = $this->answers_ids;
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-question-display.php';
    }


    private function save_question () {
        global $wpdb;
        $notice = $question_id = $save = false;
        $data = $format = [];
        $values = '';
        // Save Question
        if ( isset($this->request['question-id']) && !empty($this->request['question-id']) )
            if ( !is_numeric($this->request['question-id']) || $this->request['question-id'] < 1 ) {
                $notice = '<strong>Not saved</strong>. Question id error ';
            } else {
                $question_id = $data ['id'] = (int)$this->request['question-id'];
                $format[] = '%d';
            }
        if ( !isset($this->request['question-text']) || strlen($this->request['question-text']) < 2 || mb_strlen($this->request['question-text']) > 250 )
            $notice .= '<strong>Not saved</strong>. Question lengh error ';
        else {
            $data['text'] = $this->request['question-text'];
            $format[] = '%s';
        }
        if ( isset($this->request['question-explanation']) && mb_strlen($this->request['question-explanation']) > 250 )
            $notice .= '<strong>Not saved</strong>. Question explanation lengh error ';
        else {
            $data['explanation'] = $this->request['question-explanation'];
            $format[] = '%s';
        }

        // Saving question
        if ( $notice )
            echo '<div class="notice notice-error is-dismissible"><p>' . $notice . '</p></div>';
        else
            $save = $wpdb->replace( $wpdb->prefix . 'sqhs_questions', $data, $format );
        if ( $save === false ) {
            $notice = '<strong>Not saved</strong>. Question saving error at server ';
        } else {
            $question_id = $wpdb->insert_id;
        }
        

        $data = false;
        if ( !$notice && $question_id && $question_id > 0 ) {
            // Save Answers
            if ( !empty($this->request['answers_ids']) )
                $data = explode( ",", $this->request['answers_ids'] );
            if ( $data ) {
                foreach ( $data as $i ) {
                    if ( isset($this->request[('answer_text_' . $i)]) && mb_strlen($this->request[('answer_text_' . $i)]) > 0 ) {
                        $text = $this->request[('answer_text_' . $i)];
                        $text = ( mb_strlen($text) > 49 ) ?  substr($text, 0, 49) : $text;
                        $correct = ( isset($this->request[('answer_correct_' . $i)]) && ($this->request[("answer_correct_" . $i)] == "on" || $this->request[("answer_correct_" . $i)] == "1") ) ? 1 : 0;
                        $values .= '(' . $question_id . ',"' . $text . '",' . $correct . '),';
                    }
                }
                // Deleting all previous Answers
                $wpdb->delete( $wpdb->prefix . 'sqhs_answers', ['question_id'=>$question_id], '%d' );
                // Cut last comma and Insert Answers
                if ( strlen($values) > 6 ) {
                    $values = substr( $values, 0, (strlen($values) - 1) );
                    $wpdb->query( 'INSERT INTO ' . $wpdb->prefix .'sqhs_answers (question_id,text,correct) VALUES ' . $values );
                }

            }

            $values = '';
            // Deleting all previous Relationships
            $wpdb->delete( $wpdb->prefix . 'sqhs_relationships', ['question_id'=>$question_id, 'set_id'=>null], ['%d', '%d'] );
            // Save Relationships
            if ( isset($this->request['set_category']) && is_countable($this->request['set_category']) ) {
                foreach ( $this->request['set_category'] as $i ) {
                    if ( is_numeric($i) && $i > 0 ) {
                        $values .= '(NULL,' . (int)$i . ',' . $question_id . '),';
                    }
                }
                // Cut last comma and Insert Relationships
                if ( strlen($values) > 8 ) {
                    $values = substr( $values, 0, (strlen($values) - 1) );
                    $wpdb->query( 'INSERT INTO ' . $wpdb->prefix .'sqhs_relationships (set_id,category_id,question_id) VALUES ' . $values );
                }
            }
            $sqhs_questions = $this;
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-questions-display.php';
        } else {
            $data = [];
            $data['id'] = $question_id;
            $this->get_data( $data );
            $this->get_answers_list($question_id);

            $this->show_question($question_id);
        }

    }

	/**
	 * @param int $id
	 */
    private function delete_question($id) {
	    global $wpdb;
	    $wpdb->query( 'START TRANSACTION' );
	    $T1 = $wpdb->delete( $wpdb->prefix . 'sqhs_relationships', [ 'question_id' => $id, 'set_id' => null ], [ '%d', '%d' ] );
	    $T2 = $wpdb->delete( $wpdb->prefix . 'sqhs_answers', [ 'question_id' => $id ], '%d' );
	    $T3 = $wpdb->delete( $wpdb->prefix . 'sqhs_questions', [ 'id' => $id ], '%d' );
	    if ( $T1 === false || $T2 === false || $T3 === false ) {
		    $wpdb->query( 'ROLLBACK' );
	    } else {
		    $wpdb->query( 'COMMIT' );
	    }
    }

}
