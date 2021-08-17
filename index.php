<?PHP // Ş-UTF8, 10.09.2019
session_start();
ob_start();

// Genel sistem ayarlarını yüklüyoruz
// ----------------------------------------------------------
include_once('config.php');

if (_P_PRF_USE_GZIP) { @ob_start("ob_gzhandler"); }
if (_P_LCL_TIMEZONE != "") { @date_default_timezone_set(_P_LCL_TIMEZONE); }

if (_P_SEC_DEV_MODE) {
	error_reporting(E_ALL);
	$page_start_time = microtime(TRUE);
	$page_start_memory = memory_get_usage();
}
else { error_reporting(0); }

if (extension_loaded('xdebug')) {
    xdebug_enable();
    xdebug_start_trace();
}

include_once(_P_FLD_TEMPLATES."inc_classes.php");
// ----------------------------------------------------------

// ----------------------------------------------------------
$load_google_charts = false;

if (_P_SEC_ASK_PASS_CHANGE == true AND isset($_SESSION["user_change_pass"]) AND $_SESSION["user_change_pass"] == true) {
	$params_load_wrappers = false;
}

if (isset($params_load_wrappers) AND $params_load_wrappers == true) {
	include_once(_P_FLD_TEMPLATES."header.php");
	include_once(_P_FLD_TEMPLATES.'wrappers_top.php');
}
else { include_once(_P_FLD_TEMPLATES."header_nowrap.php"); }

if (_P_SEC_MAINTENANCE_MODE == true AND isset($params_module_dir) AND $params_module_dir != "hesap-islemleri" AND isset($_SESSION['user_yetki_id']) AND $_SESSION['user_yetki_level'] != 9) {
	include_once(_P_FLD_MODULES.'error/maintenance.php');
}
elseif (_P_SEC_ASK_PASS_CHANGE == true AND isset($_SESSION["user_change_pass"]) AND $_SESSION["user_change_pass"] == true AND ($params_module_dir != "hesap-islemleri" OR ($params_module_dir == "hesap-islemleri" AND $params_module_view != 'logout'))) {
	include_once(_P_FLD_MODULES.'hesap-islemleri/change-password.php');
}
elseif (isset($params_module_dir) AND $params_module_dir != "" AND $params_module_view != "") {
	if (!$auth->check("access")) {
		if (file_exists(_P_FLD_MODULES.'error/access.php')) {
			include_once(_P_FLD_MODULES.'error/access.php');
		}
	}
	elseif (file_exists(_P_FLD_MODULES.$params_module_dir.'/'.$params_module_view.'.php')) {
		include_once(_P_FLD_MODULES.$params_module_dir.'/'.$params_module_view.'.php');
	}
}
elseif (file_exists(_P_FLD_MODULES.'error/404.php')) { 
	include_once(_P_FLD_MODULES.'error/404.php');
}

if (isset($params_load_wrappers) AND $params_load_wrappers == true) {
	include_once(_P_FLD_TEMPLATES.'wrappers_bottom.php');
	include_once(_P_FLD_TEMPLATES."footer.php");
}
else { include_once(_P_FLD_TEMPLATES."footer_nowrap.php"); }
// ----------------------------------------------------------
?>