<?php


class Categories_List extends \WP_List_Table
{
    private $categories;

    function __construct($request) {

        if ( !current_user_can('manage_categories') ) {
            $err_message = 'Access level error';
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-err-display.php';
            wp_die();
        }

        parent::__construct( array(
            'singular'=> 'Category', //Singular label
            'plural' => 'Categories', //plural label, also this well be one of the table css class
            'ajax' => true //We won't support Ajax for this table
        ) );

        $this->check_action($request);
    }


    function get_columns(){
        $columns = array(
            'name' => 'Category name',
            'description' => 'Description'
        );
        return $columns;
    }


    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->get_data();
        if ($this->categories) {
            usort($this->categories, array(&$this, 'usort_reorder'));

            $per_page = 5;
            $current_page = $this->get_pagenum();
            $total_items = count($this->categories);
            $sliced_array = [];
            for ($i = (($current_page - 1) * $per_page); $i < ((($current_page - 1) * $per_page) + $per_page); $i++) {
                if (isset($this->categories[$i]))
                    $sliced_array[] = $this->categories[$i];
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
            case 'name':
            case 'description':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'name'  => array('name', true),
            'description' => array('description', false)
        );
        return $sortable_columns;
    }


    function column_name($item) {
        $edit_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=edit&cat=' . $item['id']), 'edit' );
        $del_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=delete&cat=' . $item['id']), 'delete' );
        $actions = [
            'edit' => '<a href="' . $edit_url . '">' . _('Edit') . '</a>',
            'delete' => '<a href="' . $del_url . '">' . _('Delete') . '</a>',
        ];

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
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
     * Loads Categories into $categories
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

        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'sqhs_categories ' . $where . ' ORDER BY name';
        $this->categories = $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * @param array $data ['name', 'description']
     * @return false|int
     */
    private function add_data($data) {
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'sqhs_categories',
            [ 'name' => $data['name'], 'description' => $data['description'] ]
        );
        return $result;
    }

    function no_items() {
        _e( 'No Categories found.' );
    }


    /**
     * @param array $data ['name', 'description']
     */
    private function add_category($data) {
        // Check fields length
        if ( mb_strlen($data['name']) > 49 || mb_strlen($data['description']) >250 ) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>';
            _e( 'Category was not added.' );
            echo '</strong> ';
            _e( 'Data length is too big', $data['name'] );
            echo '</p></div>';
            return;
        }

        // Check if such name is already exist
        $this->get_data( [ 'name' => $data['name'] ] );
        if ( !empty($this->categories) ) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>';
            _e('Category was not added.');
            echo '</strong> ';
            _e(sprintf('Category %s already exists', $data['name']));
            echo '</p></div>';
            return;
        }

        // Adding new category and showing message
        $result = $this->add_data($data);
        if ( !$result ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>';
            _e( 'Category was not added.' );
            echo '</strong> ';
            _e( 'Insert error' );
            echo '</p></div>';
            return;
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>';
            _e( sprintf('Category %s was created', $data['name']) );
            echo '</p></div>';
        }
    }

    /**
     * @param $request it is the $_REQUEST
     */
    private function check_action($request) {
        if (isset($request['action']) && isset($request['cat'])) {
            if (wp_verify_nonce($request['_wpnonce'], 'edit')) {
                /** @ToDo Complete Category Edit */
                echo 'Edit';
            }
            if (wp_verify_nonce($request['_wpnonce'], 'delete')) {
                /** @ToDo Complete Category Delete */
                echo 'Delete';
            }
            // action = 'add category'
            if ( isset($request['_wpnonce']) && $request['action'] == 'add-cat' && wp_verify_nonce($request['_wpnonce'], 'add-cat') ) {
                $data = [
                    'name' => sanitize_text_field($_REQUEST['cat-name']),
                    'description' => sanitize_textarea_field($_REQUEST['cat_descr'])
                ];
                $this->add_category($data);
            }
        }
    }


    /**
     * Builds and returns <li>-list of Categories
     *
     * @param array $relationships Array of category_id
     * @return string
     */
    function get_categories_list($relationships = null) {
        $this->get_data();

        $li = '';
        if ( !empty($this->categories) ) {
            foreach ($this->categories as $category) {

                $checked = '';
                if ( !is_null($relationships) )
                    if ( in_array($category['id'], $relationships) )
                        $checked = 'checked="checked"';

                $li .= '<li title="' . $category['description'] . '"><label>';
                $li .= '<input type="checkbox" name="set_category[]" value="' . $category['id'] . '" ' . $checked . '> ' . $category['name'] . '</label></li>';
            }
        }

        return $li;
    }
}
