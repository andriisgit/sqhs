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


    function sqhs_show_quiz($atts) {
        $atts = shortcode_atts( ['set' => '0'], $atts, 'SQHS' );
	    ob_start();
        require_once plugin_dir_path(__FILE__) . 'partials/sqhs-quiz_begin.php';
        return ob_get_clean();
    }


    public function quiz_begin() {

        check_ajax_referer();

        ob_start();
        require_once plugin_dir_path(__FILE__) . 'partials/sqhs-quiz.php';
        return ob_get_clean();

        $response['result'] = 'Hey';
        $response['message'] = 'MeYHEY';
        echo json_encode($response);
        wp_die();

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sqhs-public.js', array( 'jquery' ), $this->version, false );
		//wp_enqueue_script( $this->plugin_name . '-react-dev', 'https://unpkg.com/react@16/umd/react.development.js' );
		//wp_enqueue_script( $this->plugin_name . '-react-dom-dev', 'https://unpkg.com/react-dom@16/umd/react-dom.development.js', $this->plugin_name . '-react-dev' );

        wp_localize_script( $this->plugin_name, 'wp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce()
        ) );

	}


	function sqhs_add_shortcode() {
	    add_shortcode('SQHS', [ $this, 'sqhs_show_quiz' ]);
    }



}
