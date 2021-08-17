<?PHP // ŞUTF8 17.07.2019
if (!defined("_P_SEC_CKE_MIX")) { ?>You don't have permission to access this file.<?PHP exit; }

function reload_cache() {
	global $cache;
	$cache->reload(array("code" => "cities"));
	if ($cache->error) { return array(false, $cache->error_msg); }
	return array(true, "");
}

if (isset($_GET['opt']) AND (($_GET['opt'] == "add" AND $auth->check_alert("ekle")) OR ($_GET['opt'] == "edit" AND $auth->check_alert("duzenle")))) {
	$uid = 0; if ($_GET['opt'] == "edit") { $uid = intval($_GET['id']); }

	// Liste ekranı parametreleri
	//---------------------------------------------------------------------
	$required_list = array(array("city_name"), array("city_code"), array("status"));

	$default_load_options = array(
		"table-name" => _P_DB_PREFIX."CITY_ID",
		"col-list" => array(
			array("col-name" => "city_name", "input-view" => "İl Adı"),
			array("col-name" => "city_code", "input-view" => "İl Kodu"),
			array("col-name" => "status", "input-view" => "Durum", "input-type" => "select", "select-default-value" => "1", 
				"value-type" => "array", "value-data" => array(
				array("value" => "0", "label" => "Pasif"),
				array("value" => "1", "label" => "Aktif"), 
				array("value" => "2", "label" => "Kilitli", "auth" => $auth->check_user_level("9")),
				array("value" => "3", "label" => "Silinmiş", "auth" => $auth->check_user_level("9"))
			))
		),
		"required-list" => $required_list
	);
	if ($_GET['opt'] == "edit" AND $uid != 0) {
		// :uid = $uid
		$default_load_data_sql = "SELECT * FROM `"._P_DB_PREFIX."CITY_ID` WHERE `city_id` = '".$uid."'";
	}
	//---------------------------------------------------------------------

	// Kayıt işlemi parametreleri
	//---------------------------------------------------------------------
	$default_save_options = array(
		"table-name" => _P_DB_PREFIX."CITY_ID",
		"table-short-name" => "a",
		"col-list" => "*",
		"except-list" => array("city_id"),
		"required-list" => $required_list
	);
	if ($_GET['opt'] == "edit" AND $uid != 0) {
		$default_save_options["where"] = "`city_id` = '".$uid."'";
	}
	//---------------------------------------------------------------------

	// Root yetkisi olmayan üyelerin, silinen, kilitlenen kayıtları görmesini engelliyoruz
	//---------------------------------------------------------------------
	if ($_GET['opt'] == "edit" AND $uid != 0 AND !$auth->check_user_level("9")) {
		$hide_if_dont_have_auth = "SELECT COUNT(`city_id`) AS total FROM `"._P_DB_PREFIX."CITY_ID` 
				WHERE `city_id` = '".$uid."' AND `status` IN (0,1)";
	}
	//---------------------------------------------------------------------

	// BU KISMIN ALTINDAKİLERİ DÜZENLEMENİZE GEREK YOK
	//---------------------------------------------------------------------
	//---------------------------------------------------------------------

	// Sayfa başlığı parametreleri
	//---------------------------------------------------------------------
	if ($_GET['opt'] == "add") { $title_opt = "Yeni Kayıt"; }
	elseif ($_GET['opt'] == "edit") { $title_opt = "Kayıt Düzenle, ID: ".$uid; }

	$forms->page_options(array(
		"title" => $params_page_title." Düzenle &bull; ".$title_opt,
		"icon" => '<span class="ds-icon font-green" style="margin-right: 5px;">'.$params_module_view_icon.'</span>'
	));
	//---------------------------------------------------------------------

	// Silinen veya Kilitlenen kayıtları, üyelerin görmesini engelliyoruz.
	//---------------------------------------------------------------------
	if ($_GET['opt'] == "edit" AND $uid != 0 AND !$auth->check_user_level("9")) {
		$s = $db->query($hide_if_dont_have_auth);
		if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP exit; }
		$k = $db->fetch_assoc($s);
		if ($k['total'] == 0) { ?><div class="alert alert-danger"><strong>Hata!</strong>: Kayıt bulunamadı, silinmiş veya taşınmış olabilir.</div><?PHP exit;}
	}
	//---------------------------------------------------------------------

	if ($_GET['opt'] == "add") {
		// Kayıt formunun linki
		// -------------------------------------------------------------------------------------
		$forms->set_url(_P_FLE_SCRIPT_NAME.'?module='.@$_GET['module'].'&view='.@$_GET['view'].'&opt=add');
		// -------------------------------------------------------------------------------------

		// Kayıt işlemleri
		// -------------------------------------------------------------------------------------
		if (isset($_POST["approve"])) {
			$sql_options = array($default_save_options);
			$sql_status = $forms->run_sql("insert", $sql_options, $_POST);
			$show_msg = $sql_status["output"];
			reload_cache();
		}
		// -------------------------------------------------------------------------------------

		// Listeleme işlemleri
		// -------------------------------------------------------------------------------------
		$forms->load(array($default_load_options));
		if (isset($show_msg) AND !empty($show_msg)) { echo $show_msg; }
		$output = $forms->create();
		echo $output;
		// -------------------------------------------------------------------------------------
	}
	elseif ($_GET['opt'] == "edit" AND isset($_GET['id'])) {
		$uid = intval($_GET['id']);
		
		// Kayıt formunun linki
		// -------------------------------------------------------------------------------------
		$forms->set_url(_P_FLE_SCRIPT_NAME.'?module='.@$_GET['module'].'&view='.@$_GET['view'].'&opt=edit&id='.$uid);
		// -------------------------------------------------------------------------------------

		// Kayıt işlemleri
		// -------------------------------------------------------------------------------------
		if (isset($_POST["approve"])) {
			$sql_status = $forms->run_sql("update", array($default_save_options), $_POST);
			$show_msg = $sql_status["output"];
			reload_cache();
		}
		// -------------------------------------------------------------------------------------

		// Listeleme işlemleri
		// -------------------------------------------------------------------------------------
		$default_load_options["load-value-from"] = $default_load_data_sql;
		$forms->load(array($default_load_options));
		if (isset($show_msg) AND !empty($show_msg)) { echo $show_msg; }
		$output = $forms->create();
		echo $output;
		// -------------------------------------------------------------------------------------
	}
	else { ?><div class="alert alert-danger"><strong>Hata!</strong> Eksik parametre "id".</div><?PHP }
}
else {
	if (isset($_GET['opt'])) {
		if ($_GET['opt'] == "passive" AND isset($_GET['id']) AND $auth->check_alert("duzenle")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."CITY_ID` SET `status` = 0 WHERE `city_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else { ?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla pasifleştirildi..</div><?PHP 
				reload_cache(); }
		}
		elseif ($_GET['opt'] == "active" AND isset($_GET['id']) AND $auth->check_alert("duzenle")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."CITY_ID` SET `status` = 1 WHERE `city_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else { ?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla aktifleştirildi..</div><?PHP 
				reload_cache(); }
		}
		elseif ($_GET['opt'] == "delete" AND isset($_GET['id']) AND $auth->check_alert("sil")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."CITY_ID` SET `status` = 3 WHERE `city_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else { ?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla silindi.</div><?PHP 
				reload_cache(); }
		}
	}

	if (!isset($_GET['hide-details'])) {
		$sql_select = "a.`city_id`, a.`city_name`, a.`city_code`, a.`status`";

		$sql = "SELECT {SELECTQUERY}
			FROM `"._P_DB_PREFIX."CITY_ID` a";

		$where_query = "";
		if (!$auth->check_user_level("9")) { $where_query = "a.`status` IN (0,1)"; }

		$options = array(
			"title" => $params_page_title." Listesi",
			"icon" => '<span class="ds-icon font-green">'.$params_module_view_icon.'</span>',
			"db-select" => $sql_select,
			"db-where" => $where_query,
			"db-sql" => $sql,
			"search-except" => array("status"),
			"rename-cols" => array(
				array("city_id", "ID"),
				array("city_name", "İl Adı"),
				array("city_code", "İl Kodu"),
				array("status", "Durum")
			),
			"format-cols" => array(
				array("col-name" => "status", "format" => "dsstatus")
			),
			"link-add-button" => "popup.php?module=".@$_GET['module'].'&view='.@$_GET['view'].'&opt=add',
			"link-add-button-onclick" => "datatable_load_popup('{_link}');",
			"filter-options" => array(
				array("title" => "ID", "col-name" => "city_id", "filter-type" => "%like%"),
				array("title" => "İl Adı", "col-name" => "city_name", "filter-type" => "%like%"),
				array("title" => "İl Kodu", "col-name" => "city_code", "filter-type" => "%like%"),
				array("title" => "Durum", "col-name" => "status", "filter-type" => "in", "filter-value-type" => "i", "input-type" => "select", 
					"value-type" => "array", "select-multiple" => true, "value-data" => array(
						array("text" => "Pasif", "value" => "0"),
						array("text" => "Aktif", "value" => "1"),
						array("text" => "Kilitli", "value" => "2", "auth" => $auth->check_user_level("9")),
						array("text" => "Silinmiş", "value" => "3", "auth" => $auth->check_user_level("9"))
					)
				)
			),
			"options" => array(
				"option-show" => true,
				"option-title" => "İşlemler",
				"option-width" => "50px",
				"option-button-title" => "İşlemler",
				"option-button-icon" => '<i class="fa fa-sign-in" aria-hidden="true"></i>',
				"option-style" => "list",
				"option-position" => "left",
				"option-list" => array(
					array("title" => "Aktif Yap", "icon" => '<i class="fa fa-eye"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=active&id={city_id}&hide-details=1',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "0"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Pasif Yap", "icon" => '<i class="fa fa-eye-slash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=passive&id={city_id}&hide-details=1',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "1"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Düzenle", "icon" => '<i class="fa fa-edit"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=edit&id={city_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Sil", "icon" => '<i class="fa fa-trash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=delete&id={city_id}&hide-details=1',
						"onclick" => "datatable_delete('{city_name}', '{_link}');", "hide-if" => array(
							array("type" => "=", "colname" => "status", "value" => "3"),
							array("type" => "auth", "value" => $auth->check("sil"))
						)
					)
				)
			)
		);

		if ($auth->check("ekle") == false) {
			$options['hide-add-button'] = true;
		}

		$datatable->load($options);
		echo $datatable->output();
	}
}
?>