<?php


class Sets_List extends \WP_List_Table
{
    private $sets;

    function __construct($request) {

        if ( !current_user_can('manage_categories') ) {
            $err_message = 'Access level error';
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-err-display.php';
            wp_die();
        }
        global $status, $page;

        parent::__construct( array(
            'singular'=> 'Set', //Singular label
            'plural' => 'Sets', //plural label, also this well be one of the table css class
            'ajax' => true //We won't support Ajax for this table
        ) );

        $this->check_action($request);

    }


    function get_columns() {
        $columns = array(
            'id' => 'ID',
            'name' => 'Set name',
            'description' => 'Set description'
        );
        return $columns;
    }


    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->get_data();
        if ( $this->sets ) {
            usort( $this->sets, array(&$this, 'usort_reorder') );

            $per_page = 5;
            $current_page = $this->get_pagenum();
            $total_items = count($this->sets);
            $sliced_array = [];
            for ( $i = (($current_page - 1) * $per_page); $i < ((($current_page - 1) * $per_page) + $per_page); $i++ ) {
                if ( isset($this->sets[$i]) )
                    $sliced_array[] = $this->sets[$i];
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
            case 'name':
            case 'description':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'id'  => array('name', false),
            'name'  => array('name', true)
        );
        return $sortable_columns;
    }


    function column_name($item) {
        $edit_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=edit&set=' . $item['id']), 'edit' );
        $del_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=delete&set=' . $item['id']), 'delete' );
        $actions = [
            'edit' => '<a href="' . $edit_url . '">' . _('Edit') . '</a>',
            'delete' => '<a href="' . $del_url . '">' . _('Delete') . '</a>',
        ];

        return sprintf( '%1$s %2$s', $item['name'], $this->row_actions($actions) );
    }


    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';
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
     * @param array $params may contain [or 'name' or 'id']
     */
    private function get_data($params = null) {
        global $wpdb;
        $where = '';
        if ( !empty($params['name']) )
            $where .= ' WHERE name LIKE \'' . $params['name'] . '\' ';
        if ( !empty($params['id']) )
            $where == '' ? $where = ' WHERE id=' . $params['id'] : $where .=  ' AND id=' . $params['id'];

        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'sqhs_sets ' . $where . ' ORDER BY name';
        $this->sets = $wpdb->get_results($sql, ARRAY_A);
    }


    function no_items() {
        _e( 'No Quiz sets found.' );
    }


    /**
     * @param $request it is the $_REQUEST
     */
    private function check_action($request) {
        if ( isset($request['action']) || isset($request['set']) ) {

            require_once plugin_dir_path(__FILE__) . 'class-sqhs-category.php';
            $catlist = new Categories_List($request);

            // Edit set by set_id
            if ( wp_verify_nonce($request['_wpnonce'], 'edit') ) {
                $category = $catlist->get_categories( $request['set'] );
                if ( empty($category) )
                    wp_die( _e('No data found') );
                $set = $category[0];
                $set['heading'] = 'Edit Questions Set';
                $set['subheading'] = 'Edit Set';
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-set-display.php';
            }

            if ( wp_verify_nonce($request['_wpnonce'], 'delete') ) {
                /** @ToDo Complete Set Delete */

                echo 'Delete';
                wp_die();
            }

            // Add new Set
            if ( wp_verify_nonce($request['_wpnonce'], 'addnewset') ) {

                $categories = $catlist->get_categories();

                $li = '';
                if ( !empty($categories) ) {
                    foreach ($categories as $id => $category) {
                        $li .= '<li id="category-' . $id . '" title="' . $category['description'] . '"><label>';
                        $li .= '<input type="checkbox" name="post_category[]" id="in-category-' . $id . '"> ' . $category['name'] . '</label></li>';
                    }
                }
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sqhs-set-display.php';

            }

            // Save the Set
            if ( wp_verify_nonce($request['_wpnonce'], 'saveset') ) {

            }
        }

    }
}
