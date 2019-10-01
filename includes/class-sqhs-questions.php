<?php


class Questions_List extends \WP_List_Table
{
    private $questions;     // array filled with Questions
    private $answers;       // array of Answers filled with answers for defined Question
    private $answers_block; // string contains html for output in sqhs-question-display.php
    private $answers_ids;   // string contains Answers ids for defined Question

    function __construct($request) {

        if ( !current_user_can('manage_categories') ) {
            $err_message = 'Access level error';
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-err-display.php';
            wp_die();
        }
        global $status, $page;

        parent::__construct( array(
            'singular'=> 'Question', //Singular label
            'plural' => 'Questions', //plural label, also this well be one of the table css class
            'ajax' => true //We won't support Ajax for this table
        ) );

        $this->check_action($request);

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
            'delete' => '<a href="' . $del_url . '">' . _('Delete') . '</a>',
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
     * Loads Sets into $sets
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
    private function check_action($request) {
        if ( isset($request['action']) || isset($request['question']) ) {

            require_once plugin_dir_path(__FILE__) . 'class-sqhs-categories.php';
            $catlist = new Categories_List($request);

            // Edit Question
            if ( wp_verify_nonce($request['_wpnonce'], 'edit') && is_numeric($request['question']) ) {
                $param['id'] = $request['question'];

                $relationships = $this->get_relations($param['id']);
                $relationships = array_column($relationships, 'category_id');

                $this->get_data( $param );

                $li = $catlist->get_categories_list($relationships);
                $question = $this->questions[0];
                $question['heading'] = 'Edit Question';
                $question['subheading'] = 'Question';
                $this->get_answers_list($param['id']);

                $answers_block = $this->answers_block;
                $answers_ids = $this->answers_ids;
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-question-display.php';

            }


            if ( wp_verify_nonce($request['_wpnonce'], 'delete') ) {
                /** @ToDo Complete Set Delete */

            }

            // Add new Set
            if ( wp_verify_nonce($request['_wpnonce'], 'addnewquestion') ) {

                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-question-display.php';

            }

            // Save question
            if ( wp_verify_nonce($request['_wpnonce'], 'savequestion') ) {

                echo 'Save';

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


    

}
