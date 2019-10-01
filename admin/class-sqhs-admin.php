<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       biir.dk
 * @since      1.0.0
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sqhs
 * @subpackage Sqhs/admin
 * @author     ASi <asi@biir.dk>
 */
class Sqhs_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
    public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sqhs_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sqhs_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sqhs-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

        if ( isset($_REQUEST['page']) && strpos($_REQUEST['page'], 'sqhs_') !== false ) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sqhs-admin.js', array('jquery'), $this->version, true);

            wp_localize_script($this->plugin_name, 'wp_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
        }
	}


	public function admin_menu() {
        add_menu_page(
            $this->plugin_name . ' - Quizzes',
            'Quizzes',
            'manage_options',
            'sqhs_admin_quizzes',
            [ $this, 'admin_quizzes' ],
            'dashicons-welcome-learn-more',
            25
        );
        add_submenu_page(
            'sqhs_admin_quizzes',
            $this->plugin_name . ' - Questions',
            'Questions',
            'manage_options',
            'sqhs_admin_menu_questions',
            [ $this, 'admin_questions' ]
        );
        add_submenu_page(
            'sqhs_admin_quizzes',
            $this->plugin_name . ' - Categories',
            'Categories',
            'manage_options',
            'sqhs_admin_menu_categories',
            [ $this, 'admin_categories' ]
        );
    }


    public function admin_quizzes() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sqhs-quizzes.php';
        $sqhs_sets = new Sets_List($_REQUEST);

	    if ( !isset($_REQUEST['action']) || !isset($_REQUEST['set']) )
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-sets-display.php';

    }


    public function admin_questions() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sqhs-questions.php';
        $sqhs_questions = new Questions_List($_REQUEST);

        if ( !isset($_REQUEST['action']) || !isset($_REQUEST['question']) )
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-questions-display.php';
    }


    public function admin_categories() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sqhs-categories.php';
        $sqhs_categories = new Categories_List($_REQUEST);

        require_once plugin_dir_path(__FILE__) . 'partials/sqhs-cat-display.php';
	}
	

    /**
     * Set the column width for Set list table
     */
	function admin_header() {
        $page = ( isset($_REQUEST['page'] ) ) ? esc_attr( $_REQUEST['page'] ) : false;
        if ( 'sqhs_admin_menu' != $page ) return;
        
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-name { width: 40%; }';
        echo '.wp-list-table .column-description { width: 48%; }';
        echo '.wp-list-table  column-max_question_quantity { width: 7%; }';
        echo '</style>';
    }


    /**
     * Save Quiz Set after adding/editing in admin using ajax action sqhs_setsave
     * Recieves form_data from the ajax
     * 
     * @return array $response json ['result', 'message','set_id']
     */
    function save_set() {
        if ( !wp_verify_nonce($_REQUEST['_wpnonce'], 'addnewset') ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Checking error';
        }

	    global $wpdb;
        $response['result'] = 'OK';
        $table = $wpdb->prefix . 'sqhs_sets';

	    if ( !isset($_REQUEST['set-name']) || strlen($_REQUEST['set-name']) < 1 ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Name can not be empty';
        }
        if ( strlen($_REQUEST['set-name']) > 45 || (isset($_REQUEST['set-description']) && strlen($_REQUEST['set-description']) > 240) ) {
            $response['result'] = 'ERR';
            $response['message'] = 'String is too long';
        }
        if ( !empty($_REQUEST['set-id']) && !is_numeric($_REQUEST['set-id']) ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Data value error';
        }
        if ( !empty($_REQUEST['max_que_qua']) && !is_numeric($_REQUEST['max_que_qua']) ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Question quantity can not be non numeric';
        }
        if ( $_REQUEST['max_que_qua'] > 65533 ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Question quantity is too big';
        }
        if ( $response['result'] == 'ERR' ) {
            echo json_encode($response);
            wp_die();
        }

        // check if name already exist
        if ( !empty($_REQUEST['set-id']) ) {
            $and = ' AND id <> ' . $_REQUEST['set-id'];
            $data = [
                'id' => $_REQUEST['set-id'],
                'name' => $_REQUEST['set-name'],
                'description' => $_REQUEST['set-description'],
                'max_question_quantity' => (empty($_REQUEST['max_que_qua']) || $_REQUEST['max_que_qua'] < 0) ? 0 : $_REQUEST['max_que_qua']
            ];
            $format = ['%d', '%s', '%s', '%d'];
        } else {
            $and = '';
            $data = [
                'name' => $_REQUEST['set-name'],
                'description' => $_REQUEST['set-description'],
                'max_question_quantity' => (empty($_REQUEST['max_que_qua']) || $_REQUEST['max_que_qua'] < 0) ? 0 : $_REQUEST['max_que_qua']
            ];
            $format = ['%s', '%s', '%d'];
        }
        $sql = 'SELECT id FROM ' . $table . ' WHERE name LIKE \'' . $_REQUEST['set-name'] . '\' ' . $and;
        $result = $wpdb->get_results($sql);
        if ( $result ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Set name ' . $_REQUEST['set-name'] . ' already exists';
            echo json_encode($response);
            wp_die();
        }

        // Everything is ok. Save the Set

        $result = $wpdb->replace($table, $data, $format);
        if ( $result == false ) {
            $response['result'] = 'ERR';
            $response['message'] = 'Error interacting with DB';
            echo json_encode($response);
            wp_die();
        }

        if ( empty($_REQUEST['set-id']) )
            $response['set_id'] = $wpdb->insert_id;
        else
            $response['set_id'] = $_REQUEST['set-id'];

        // Save categories relations
        $table = $wpdb->prefix . 'sqhs_relationships';

        if ( isset($_REQUEST['set_category']) ) {
            $values = '';
            foreach ($_REQUEST['set_category'] as $param) {
                $values .= '(' . $response['set_id'] . ', ' . $param . '),';
            }
            $values[(strlen($values) - 1)] = ' ';
        }

        $wpdb->delete( $table, ['set_id' => $response['set_id'], 'question_id' => NULL], '%d' );

        if ($values)
            $wpdb->query('INSERT INTO ' . $table . ' (set_id, category_id) VALUES ' . $values);

        echo json_encode($response);
        wp_die();
    }


}
