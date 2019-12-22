<?php

namespace SQHS;

class Finalization {

	/**
	 * @param int $score Percent (*100) value of the result of whole quiz
	 *
	 * @return object|null
	 */
	public static function get_finalized_data ( $score = null ){
		global $wpdb;

		if ( $score ) {
			$sql = 'SELECT text_body, image_url FROM ' . $wpdb->prefix . 'sqhs_final WHERE active=1 AND range_from<=' . $score .' AND range_to>=' . $score;
			return $wpdb->get_row( $sql );
		} else {
			$sql = 'SELECT id, active, range_from, range_to, text_body, image_url FROM ' . $wpdb->prefix . 'sqhs_final';
			return $wpdb->get_results( $sql );
		}
	}


	/**
	 * @return string $html
	 */
	public static function get_finalized_data_html () {
		$data = self::get_finalized_data();
		$html = '';
		if ( !$data ) {
			// Table is empty. Return empty fields
			$html = self::get_table_row();

		} else {
			$checked = '';
			foreach ( $data as $item ) {
				if ( $item->active ) {
					$checked = ' value="1" checked="checked" ';
				} else {
					$checked = '';
				}
				$params = [
					'id' => $item->id,
					'checked' => $checked,
					'range_from' => $item->range_from,
					'range_to' => $item->range_to,
					'text_body' => $item->text_body,
					'image_url' => $item->image_url
				];
				$html .= self::get_table_row( $params );
			}
		}
		return $html;
	}

	/**
	 * @return string|void Return message for notice
	 */
	public static function save_finalized_settings() {
		if (
			!check_admin_referer('sqhsfinalsetting') ||
			!isset( $_REQUEST['submit'] ) ||
			!isset( $_REQUEST['page'] ) ||
			$_REQUEST['page'] != 'sqhs_admin_menu_final_screen' ||
			!isset( $_REQUEST['sqhs_final_settings_sets'] )
		) return ;

		global $wpdb;
		$insert = '';
		$sets = explode( ',', $_REQUEST['sqhs_final_settings_sets'] );

		foreach ( $sets as $set ) {
			$range_to = isset( $_REQUEST['sqhs_range_to_' . $set] ) ? sanitize_key( $_REQUEST['sqhs_range_to_' . $set] ) : 0;
			$range_from = isset( $_REQUEST['sqhs_range_from_' . $set] ) ? sanitize_key( $_REQUEST['sqhs_range_from_' . $set] ) : 0;
			$text = isset( $_REQUEST['sqhs_text_' . $set] ) ? sanitize_text_field( substr($_REQUEST['sqhs_text_' . $set], 0, 250) ) : '';
			$active = ( isset( $_REQUEST['active_' . $set] ) &&
				( strtolower( $_REQUEST['active_' . $set] ) == 'on' ||
				  strtolower( $_REQUEST['active_' . $set] ) == 'yes' ||
				  $_REQUEST['active_' . $set] == 1
				) ) ? 1 : 0;
			$img_url = isset( $_REQUEST['sqhs_img_' . $set] ) ? esc_url_raw( substr($_REQUEST['sqhs_img_' . $set], 0, 1000) ) : '';
			if ( $range_to && $text ) {
				$insert .= '(' . $active . ',' . $range_from . ',' . $range_to . ',"' . $text . '","' . $img_url . '"),';
			}
		}

		if ( $insert ) {
			$insert = substr( $insert, 0, ( strlen($insert) - 1 ) );
			$wpdb->query( 'START TRANSACTION' );
			$r1 = $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'sqhs_final WHERE 1' );
			$wpdb->query( 'ALTER TABLE ' . $wpdb->prefix . 'sqhs_final AUTO_INCREMENT=1' );
			$r2 = $wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'sqhs_final (active,range_from,range_to,text_body,image_url) VALUES ' . $insert );
			if ( $r1 === false || $r2 === false ) {
				$wpdb->query( 'ROLLBACK' );
				return 'Data not saved.';
			} else {
				$wpdb->query( 'COMMIT' );
				return 'Data saved.';
			}
		}

	}


	/**
	 * @param array|null $params
	 *
	 * @return string
	 */
	protected static function get_table_row( $params = null ) {

		if ( !$params ) {
			$params = [
				'id' => '1',
				'checked' => '',
				'range_from' => '',
				'range_to' => '',
				'text_body' => '',
				'image_url' => ''
			];
		}

		return '<div id="sqhs_final_item_' . $params['id'] . '">
					<label for="active_' . $params['id'] . '">Active: <input ' . $params['checked'] . ' name="active_' . $params['id'] . '" type="checkbox" id="active"></label>
					&nbsp;&nbsp;
					<label for="sqhs_range_from_' . $params['id'] . '"><span >From:</span></label>
					<input type="number" value="' . $params['range_from'] . '" name="sqhs_range_from_' . $params['id'] . '" id="sqhs_range_from_' . $params['id'] . '" class="small-text" step="1" min="0" max="100"/>
					<label for="sqhs_range_to_' . $params['id'] . '"><span>To:</span></label>
					<input type="number" value="' . $params['range_to'] . '"name="sqhs_range_to_' . $params['id'] . '" id="sqhs_range_to_' . $params['id'] . '" class="small-text" step="1" min="0" max="100"/>
					&nbsp;&nbsp;
					<label for="sqhs_text_' . $params['id'] . '"><span >Text body:</span></label>
					<input type="text" value="' . $params['text_body'] . '"name="sqhs_text_' . $params['id'] . '" id="sqhs_text_' . $params['id'] . '" class="regular-text" title="' . $params['text_body'] . '"/>
					&nbsp;&nbsp;
					<label for="sqhs_img_' . $params['id'] . '"><span >Image URL:</span></label>
					<input type="text" value="' . $params['image_url'] . '"name="sqhs_img_' . $params['id'] . '" id="sqhs_img_' . $params['id'] . '" class="regular-text code" title="' . $params['image_url'] . '"/>
				</div>';
	}

}