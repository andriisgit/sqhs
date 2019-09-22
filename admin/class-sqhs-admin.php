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
	 * @since    1.0.0
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sqhs-admin.js', array( 'jquery' ), $this->version, false );

	}


	public function admin_menu() {
        add_menu_page(
            $this->plugin_name,
            'Simple quiz',
            'manage_options',
            'sqhs_admin_menu',
            [ $this, 'admin_index' ],
            'dashicons-welcome-learn-more',
            25
        );
        add_submenu_page(
            'sqhs_admin_menu',
            $this->plugin_name . ' - Quiz categories',
            'Quiz categories',
            'manage_options',
            'sqhs_admin_menu_quiz_categories',
            [ $this, 'admin_quiz_categories' ]
        );
        add_submenu_page(
            'sqhs_admin_menu',
            $this->plugin_name . ' - Question categories',
            'Question categories',
            'manage_options',
            'sqhs_admin_menu_question_categories',
            [ $this, 'admin_question_categories' ]
        );
    }


    public function admin_index() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sqhs-sets.php';
        $sqhs_sets = new Sets_List($_REQUEST);

	    if ( !isset($_REQUEST['action']) || !isset($_REQUEST['set']) )
            require_once plugin_dir_path(__FILE__) . 'partials/sqhs-sets-display.php';

    }


    public function admin_quiz_categories() {

    }


    public function admin_question_categories() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sqhs-category.php';
        $sqhs_categories = new Categories_List($_REQUEST);

        require_once plugin_dir_path(__FILE__) . 'partials/sqhs-cat-display.php';
	}
	

	// Set the column width for Set list table
	function admin_header() {
        $page = ( isset($_REQUEST['page'] ) ) ? esc_attr( $_REQUEST['page'] ) : false;
        if ( 'sqhs_admin_menu' != $page ) return;
        
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-name { width: 40%; }';
        echo '.wp-list-table .column-description { width: 55%; }';
        echo '</style>';
    }

}
