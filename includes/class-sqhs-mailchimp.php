<?php

namespace SQHS\Integration;

class Mailchimp {

	private static $api_key, $list_id;

	private static function add_options() {
		if ( false == get_option( 'sqhs_integration_mailchimp_api_key' ) ) {
			add_option( 'sqhs_integration_mailchimp_api_key', '', '', 'no' );
		}
		if ( false == get_option( 'sqhs_integration_mailchimp_list_id' ) ) {
			add_option( 'sqhs_integration_mailchimp_list_id', '', '', 'no' );
		}
	}

	private static function load_options() {
		self::$api_key = esc_attr( get_option( 'sqhs_integration_mailchimp_api_key' ) );
		self::$list_id = esc_attr( get_option( 'sqhs_integration_mailchimp_list_id' ) );
	}

	public static function settings_page() {
		?>
		<div class="wrap">
			<h2>Integration</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'sqhs-integration-mailchimp' );
				do_settings_sections( 'sqhs-integration-mailchimp' );

				submit_button(); ?>
			</form>
		</div><!-- /.wrap -->
		<?php
	}



	public function validate_value( $input ) {
	    $output = esc_attr( $input );
		return apply_filters( 'validate_value', $output, $input );
	}


	static function init_settings() {
		self::add_options();
		self::load_options();

		add_settings_section(
			'sqhs-integration-mailchimp',
			__( 'Sharing visitor\'s first name and email to Mailchimp', 'sqhs' ),
			'',
			'sqhs-integration-mailchimp'
		);

		add_settings_field(
			'sqhs_integration_mailchimp_api_key',
			'API key',
			function (){
				echo '<input name="sqhs_integration_mailchimp_api_key" value="' . self::$api_key . '" type="text" class="regular-text code"/>';
			},
			'sqhs-integration-mailchimp',
			'sqhs-integration-mailchimp'
		);
		register_setting( 'sqhs-integration-mailchimp', 'sqhs_integration_mailchimp_api_key', [
			'type' => 'string',
			'sanitize_callback' => [ (new Mailchimp), 'validate_value' ],
			'show_in_rest' => false
		] );

		add_settings_field(
			'sqhs_integration_mailchimp_list_id',
			'list ID',
			function (){
				echo '<input name="sqhs_integration_mailchimp_list_id" value="' . self::$list_id  . '" type="text" class="regular-text code"/>';
			},
			'sqhs-integration-mailchimp',
			'sqhs-integration-mailchimp'
		);
		register_setting( 'sqhs-integration-mailchimp', 'sqhs_integration_mailchimp_list_id', [
			    'type' => 'string',
			    'sanitize_callback' => [ (new Mailchimp), 'validate_value' ],
			    'show_in_rest' => false
            ] );
	}


	public static function save( $name = '', $email ) {
		self::load_options();
		if ( self::$api_key && self::$list_id && $email ) {

			$re = '/[\d]+$/';
			preg_match_all( $re, self::$api_key, $matches, PREG_SET_ORDER, 0 );

			$link_num = ( isset( $matches[0][0] ) ) ? $matches[0][0] : ''; // регулярным выражением извлечем из него номер хоста вашего аккаунта

			$url = 'https://us' . $link_num . '.api.mailchimp.com/3.0/lists/' . self::$list_id . '/members';

			$args     = [
				'sslverify' => false,
				'timeout'   => '10',
				'headers'   => [
					'Authorization' => 'Basic ' . base64_encode( 'user:' . self::$api_key ),
					'Content-Type'  => 'application/json'
				],
				'body'      => json_encode( [
					'email_address' => $email,
					'merge_fields'  => [ 'FNAME' => $name ],
					'status'        => 'subscribed'
				] )
			];
			$response = wp_remote_post( $url, $args );
		}
	}

}