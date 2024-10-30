<?php
/*
Plugin Name: Search log
Plugin URI: http://cameronharwick.com/
Description: Keeps a log of what people search for on your site
Version: 1.0.1
Author: C Harwick
Author URI: http://cameronharwick.com/
*/

/*
 * SETUP
 */
register_activation_hook(__FILE__, function() {
	global $wpdb;
	$slog_db_version = '1';

	$table_name = $wpdb->prefix . 'searchlog';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		term varchar(255) DEFAULT '' NOT NULL,
		ip varchar(55) DEFAULT '' NOT NULL,
		agent varchar(255) DEFAULT '' NOT NULL,
		country varchar(2) DEFAULT '',
		region varchar(3) DEFAULT '',
		city varchar(255) DEFAULT '',
		PRIMARY KEY id (id)
	) $charset_collate;";

	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('slog_db_version', $slog_db_version);
});

/*
 * RETRIEVAL FUNCTIONS
 */

//Leave blank for all terms; otherwise paginates based on 10 per page
function searchlog_get_records($page=0,$onpage=10) {
	global $wpdb;
	$page = (int) $page; $onpage = (int) $onpage;
	$query = 'SELECT * FROM '.$wpdb->prefix.'searchlog ORDER BY time DESC';
	if ($page) $query .= " LIMIT ".(($page-1)*$onpage).",{$onpage}";
	$records =  $wpdb->get_results($query, ARRAY_A);
	foreach ($records as &$r) {
		$r['time'] = strtotime($r['time']);
		$r['link'] = get_search_link($r['term']);
	}
	return $records;
}

//Output results to admin
add_action('wp_ajax_slog_records', function() {
	header('Content-type: application/json');
	if (!current_user_can('edit_dashboard')) wp_die();
	
	$records = searchlog_get_records($_POST['page'], 10);
	echo json_encode($records);
	wp_die();
});

//Delete rows
add_action('wp_ajax_slog_delete', function() {
	header('Content-type: application/json');
	if (!current_user_can('edit_dashboard')) wp_die();
	
	global $wpdb;
	$d = $wpdb->delete($wpdb->prefix.'searchlog', ['id' => (int) $_POST['id']], ['%d']);
	
	echo json_encode(['status' => $d]);
	wp_die();
});

/*
 * WRITE LOGS
 */
add_action('pre_get_posts', function($query) {
	if (!is_search() || !$query->is_main_query() || is_user_logged_in()) return;
	
	global $wpdb;
	$ip = $_SERVER['REMOTE_ADDR'];
	$term = stripslashes(get_search_query());
	$table = $wpdb->prefix . 'searchlog';
	$agent = $_SERVER['HTTP_USER_AGENT'];
	
	//Ignore stupid crap
	$term_blacklist = ['tweet'];
	$agent_blacklist = ['bingbot'];
	if (in_array(strtolower($term),$term_blacklist)) return;
	foreach ($agent_blacklist as $a) if (strpos($a,$agent) !== false) return;

	//Don't let refreshes clutter up the log
	$sq = $wpdb->prepare(
		"SELECT * FROM $table WHERE ip=%s AND term=%s",
		$ip, $term
	);
	$already = $wpdb->get_row($sq, ARRAY_A);

	if (is_null($already)) {
		
		//Geolocate
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, "https://freegeoip.net/json/{$ip}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$geodata = json_decode(curl_exec($ch),true);
		curl_close($ch);		
				
		$wpdb->insert(
			$table,
			[	'time' => date('Y-m-d H:i:s'),
				'term' => $term,
				'ip'   => $ip,
				'agent' => $agent,
				'country' => $geodata['country_code'],
				'region' => $geodata['region_code'],
				'city' => $geodata['city']
			]
		);
	
	} else $wpdb->update(
		$table,
		[	'time' => date('Y-m-d H:i:s'),
			'agent' => $_SERVER['HTTP_USER_AGENT']
		],
		[ 'id' => $already['id'] ]
	);

}, 1 );

/*
 * ADMIN WIDGET
 */
add_action('wp_dashboard_setup', function() {
	wp_add_dashboard_widget(
		'slog_dashboard_widget',	// Widget slug
		'Recent Searches',			// Title
		'slog_widget_contents'		// Output function
	);	
});

function slog_widget_contents() {
	global $wpdb;
	$records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}searchlog");
	wp_enqueue_style('slog-css', plugin_dir_url(__FILE__).'search-log.css', [], null, 'all');
	
	if ($records) { ?>
		<script type="text/javascript">var slog_numsearches = <?php echo $records; ?>, slog_dir = '<?php echo plugin_dir_url(__FILE__); ?>';</script>
		<table class="widefat" id="searchlog" cellspacing="0">
			<thead>
				<tr>
					<td class="column-date">Date</td>
					<td class="column-term">Term</td>
					<td class="column-ip" colspan="2">IP</td>
				</tr>
			</thead>
			<tbody class="list:user user-list"><!-- Filled in with Javascript --></tbody>
		</table>
		<?php wp_enqueue_script('slog_widget', plugin_dir_url(__FILE__).'widget.js', ['jquery'], null, true);
	} else { ?>
		<p class="slog-noresults">No recent searches</p>
	<?php } ?>
<?php }