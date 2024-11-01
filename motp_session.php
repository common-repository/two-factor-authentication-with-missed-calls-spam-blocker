<?
define ("VER_DB", $table_prefix . "verifications2");
function get_ver_field($field,$tb=VER_DB) {
	global $wpdb;
	$sid = get_sid();
	$ret = $wpdb->get_var( "SELECT $field from $tb where session='{$sid}'" );
	return $ret;
}

function set_ver_field($field, $val,$tb=VER_DB) {
	global $wpdb;
	$sid = get_sid();
	$wpdb->query("update $tb set $field='{$val}' where session='{$sid}'");
}

function get_sid($tb=VER_DB) {
	$sid = md5( $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']); 
	return $sid;

}

function get_details($tb=VER_DB) {
global $wpdb;
	$sid = get_sid();
	$ret = $wpdb->get_row("SELECT * from $tb WHERE session = '{$sid}'");
	return $ret;
}


function create_session ($tb=VER_DB) {
	global $wpdb;
	$sid = get_sid();
	
	$id = $wpdb->get_var( "SELECT id from $tb where session='{$sid}'" );
	if (!$id) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$sql = "insert into $tb (ts, session, status,ip) values (now(), '{$sid}', 1,'{$ip}')";
		$wpdb->query($sql);
	}
	
}
?>