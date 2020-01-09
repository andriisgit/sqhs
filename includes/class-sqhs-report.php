<?php
/**
 * Created by PhpStorm.
 * User: user1
 * Date: 22.12.2019
 * Time: 19:11
 */

namespace SQHS;

class Report extends \WP_List_Table
{
	private $quizzes;
	private $per_page;

	function __construct($request) {

		if ( !current_user_can('manage_categories') ) {
			$err_message = 'Access level error';
			require_once plugin_dir_path(__FILE__) . 'partials/sqhs-err-display.php';
			wp_die();
		}
		global $status, $page;

		parent::__construct( array(
			'singular'=> 'Quiz', //Singular label
			'plural' => 'Quizzes', //plural label, also this well be one of the table css class
			'ajax' => false // support Ajax for this table
		) );

		$this->per_page = 10;

//		$this->check_action($request);

	}


	function get_columns() {
		$columns = array(
			'start_time' => 'Data',
			'end_time' => 'Duration',
			'email' => 'Email',
			'questions_ids' => 'Num of questions',
			'result' => 'Result'
		);
		return $columns;
	}


	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->get_data();
		if ( $this->quizzes ) {
			usort( $this->quizzes, array(&$this, 'usort_reorder') );

			$per_page = $this->per_page;
			$current_page = $this->get_pagenum();
			$total_items = count($this->quizzes);
			$sliced_array = [];
			for ( $i = (($current_page - 1) * $per_page); $i < ((($current_page - 1) * $per_page) + $per_page); $i++ ) {
				if ( isset($this->quizzes[$i]) )
					$sliced_array[] = $this->quizzes[$i];
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
			case 'start_time':
			case 'end_time':
			case 'email':
			case 'questions_ids':
			case 'result':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}


	function get_sortable_columns() {
		$sortable_columns = array(
			'start_time' => [ 'start_time', true ],
			'result' => [ 'result', true ]
		);
		return $sortable_columns;
	}


/*
	function column_start_time($item) {
		$view_url = wp_nonce_url( ('?page=' . $_REQUEST['page'] . '&action=view&set=' . $item['id']), 'view' );
		$actions = [
			'view' => '<a href="' . $view_url . '">' . _('View') . '</a>',
		];

		return sprintf( '%1$s %2$s', $item['start_time'], $this->row_actions($actions) );
	}
*/

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
	 * Loads completed quizzes into $quizzes
	 */
	private function get_data() {
		global $wpdb;

		$where = ' WHERE end_time IS NOT NULL AND result IS NOT NULL ';

		// start_time clause
		if ( isset($_REQUEST['m']) && strlen($_REQUEST['m']) == 6 ) {
		    $y = substr($_REQUEST['m'], 0, 4);
		    $m = substr($_REQUEST['m'], 4, 2);
		    $start_ts = mktime(0, 0, 0,$m,1,$y);
			$end_ts = mktime(0, 0, 0, (++$m), 0, $y);
			$where .= ' AND start_time>=' . $start_ts . ' AND start_time<=' . $end_ts;
        } else {
			$where .= ' AND start_time IS NOT NULL ';
        }

		// email filter clause
		if ( isset($_REQUEST['s']) && strlen($_REQUEST['s'] ) > 1 ) {
			$where .= ' AND email LIKE \'%' . $_REQUEST['s'] . '%\' ';
		}

		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'sqhs_quiz ' . $where . ' ORDER BY start_time DESC';
		$this->quizzes = $wpdb->get_results($sql, ARRAY_A);
		$this->convert_data();
	}


	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			//$echo1 = html_entity_decode(wp_referer_field(false), ENT_NOQUOTES, 'UTF-8');

			wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce', false );

			if ( isset($_REQUEST['page']) ) {
			    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
            }

		}
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
                <div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
                </div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

            <br class="clear" />
        </div>
		<?php
	}


	function no_items() {
		_e( 'No Quiz sets found.' );
	}


	/**
	 * @param $request the $_REQUEST
	 */
	private function check_action($request) {
		if ( isset($request['action']) ) {

			// Edit set by set_id
			if ( wp_verify_nonce($request['_wpnonce'], 'edit') ) {
			}

			if ( wp_verify_nonce($request['_wpnonce'], 'delete') && isset($_REQUEST['set']) && is_numeric($_REQUEST['set']) ) {
			}

		}

	}

	protected function convert_data(){
		$quizzes = $this->quizzes;
		$count = count($quizzes);
		for ($i = 0; $i < $count; $i++) {

			$datetime1 = \DateTime::createFromFormat('U', $quizzes[$i]['start_time']);
			$datetime2 = \DateTime::createFromFormat('U', $quizzes[$i]['end_time']);
			$interval = $datetime1->diff($datetime2);

			//$quizzes[$i]['start_time'] = date('Y-m-d H:i', $quizzes[$i]['start_time']);
			$quizzes[$i]['start_time'] = $datetime1->format('Y-m-d H:i');
			$quizzes[$i]['month_dropdown'] = $datetime1->format('m');
			$quizzes[$i]['year_dropdown'] = $datetime1->format('Y');
			$quizzes[$i]['end_time'] = $interval->format('%h:%i:%s');
			$quizzes[$i]['questions_ids'] = count(json_decode($quizzes[$i]['questions_ids']));
			$quizzes[$i]['result'] = round($quizzes[$i]['result'] * 100) . ' %';
		}
		$this->quizzes = $quizzes;
	}


	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @global WP_Locale $wp_locale
	 */
	public function my_dropdown() {
		global $wp_locale;
		$months = [];

        foreach ($this->quizzes as $quiz) {
            $months[] = [
                'year' => $quiz['year_dropdown'],
                'month' => $quiz['month_dropdown']
            ];
        }
        $month_count = count($months);
        for ($i = 0; $i < $month_count - 1; $i++) {
            if (!isset($months[$i]))
                continue;
	        for ($j = $i + 1; $j < $month_count; $j++) {
		        if ( isset($months[$j]) && $months[$i]['year'] == $months[ $j ]['year'] && $months[ $i]['month'] == $months[ $j ]['month'] ) {
			        unset( $months[$j] );
		        }
	        }
        }

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]['month'] ) )
			return;

		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
		?>
		<label for="sqhs-filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
		<select name="m" id="sqhs-filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row['year'] )
					continue;

				$month = zeroise( $arc_row['month'], 2 );
				$year = $arc_row['year'];

				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row['year'] . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
		<?php
	}

}