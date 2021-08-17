<?PHP // ŞUTF8 17.07.2019
if (!defined("_P_SEC_CKE_MIX")) { ?>You don't have permission to access this file.<?PHP exit; }

if (isset($_GET['opt']) AND (($_GET['opt'] == "add" AND $auth->check_alert("ekle")) OR ($_GET['opt'] == "edit" AND $auth->check_alert("duzenle")))) {
	$uid = 0; if ($_GET['opt'] == "edit") { $uid = intval($_GET['id']); }

	// Liste ekranı parametreleri
	//---------------------------------------------------------------------
	$required_list = array(array("title"), array("status"));

	$default_load_options = array(
		"table-name" => _P_DB_PREFIX."COLORS",
		"col-list" => array(
			array("col-name" => "title", "input-view" => "Adı"),
			array("col-name" => "orderby", "input-view" => "Sıralama (9>0)", "value" => 0, "text-type" => "number"),
			array("col-name" => "cihazrenk", "input-view" => "Cihaz Rengi?", "input-type" => "select", "select-default-value" => "1", 
				"value-type" => "array", "value-data" => array(
				array("value" => "0", "label" => "Hayır"),
				array("value" => "1", "label" => "Evet")
			)),
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
		$default_load_data_sql = "SELECT * FROM `"._P_DB_PREFIX."COLORS` WHERE `colors_id` = '".$uid."'";
	}
	//---------------------------------------------------------------------

	// Kayıt işlemi parametreleri
	//---------------------------------------------------------------------
	$default_save_options = array(
		"table-name" => _P_DB_PREFIX."COLORS",
		"table-short-name" => "a",
		"col-list" => "*",
		"except-list" => array("colors_id"),
		"required-list" => $required_list
	);
	if ($_GET['opt'] == "edit" AND $uid != 0) {
		$default_save_options["where"] = "`colors_id` = '".$uid."'";
	}
	//---------------------------------------------------------------------

	// Root yetkisi olmayan üyelerin, silinen, kilitlenen kayıtları görmesini engelliyoruz
	//---------------------------------------------------------------------
	if ($_GET['opt'] == "edit" AND $uid != 0 AND !$auth->check_user_level("9")) {
		$hide_if_dont_have_auth = "SELECT COUNT(`colors_id`) AS total FROM `"._P_DB_PREFIX."COLORS` 
				WHERE `colors_id` = '".$uid."' AND `status` IN (0,1)";
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

			if ($sql_status["status"] == true) {
				$uidx = 0;
				$insert_id_count = count($sql_status["success"]);
				if ($insert_id_count > 0) { $uidx = intval($sql_status["success"][0]["insert_id"]); }

				if ($uidx > 0) {
					reload_cache($uidx);
					$logs->add(array("id" => $uidx, "type" => "ekle"));
					if ($logs->error) {	$show_msg .= $logs->error_msg; }
				}
			}
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

			if ($sql_status["status"] == true) {
				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => $sql_status["success"][0]["changes"]));
				if ($logs->error) {	$show_msg .= $logs->error_msg; }
			}
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
		$old_val = "?";
		if (isset($_GET["old_val"])) { $old_val = $_GET["old_val"]; }

		if ($_GET['opt'] == "passive" AND isset($_GET['id']) AND $auth->check_alert("duzenle")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."COLORS` SET `status` = 0 WHERE `colors_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla pasifleştirildi..</div><?PHP 
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "0"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
		elseif ($_GET['opt'] == "active" AND isset($_GET['id']) AND $auth->check_alert("duzenle")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."COLORS` SET `status` = 1 WHERE `colors_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla aktifleştirildi..</div><?PHP 
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "1"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
		elseif ($_GET['opt'] == "delete" AND isset($_GET['id']) AND $auth->check_alert("sil")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."COLORS` SET `status` = 3 WHERE `colors_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla silindi.</div><?PHP
				$logs->add(array("id" => $uid, "type" => "sil", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "3"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
	}

	if (!isset($_GET['hide-details'])) {
		$sql_select = "a.`colors_id`, a.`title`, a.`orderby`, a.`cihazrenk`, a.`status`";
		$sql = "SELECT {SELECTQUERY} 
			FROM `"._P_DB_PREFIX."COLORS` a";

		$where_query = "";
		if (!$auth->check_user_level("9")) { $where_query = "a.`status` IN (0,1)"; }

		$options = array(
			"title" => $params_page_title." Listesi",
			"icon" => '<span class="ds-icon font-green">'.$params_module_view_icon.'</span>',
			"db-select" => $sql_select,
			"db-where" => $where_query,
			"db-sql" => $sql,
			"search-except" => array("cihazrenk", "status"),
			"rename-cols" => array(
				array("colors_id", "ID"),
				array("title", "Renk Adı"),
				array("orderby", "Sıralama (9>0)"),
				array("cihazrenk", "Cihaz Rengi?"),
				array("status", "Durum")
			),
			"format-cols" => array(
				array("col-name" => "status", "format" => "dsstatus"),
				array("col-name" => "cihazrenk", "format" => "if", "data" => array(
					array("col-name" => "cihazrenk", "if-type" => "=", "value" => "0", "then" => '<span class="label label-danger">Hayır</span>'),
					array("col-name" => "cihazrenk", "if-type" => "=", "value" => "1", "then" => '<span class="label label-success">Evet</span>'),
					array("col-name" => "cihazrenk", "if-type" => "else", "value" => "", "then" => '{cihazrenk}')
				))
			),
			"link-add-button" => "popup.php?module=".@$_GET['module'].'&view='.@$_GET['view'].'&opt=add',
			"link-add-button-onclick" => "datatable_load_popup('{_link}');",
			"filter-options" => array(
				array("title" => "ID", "col-name" => "colors_id", "filter-type" => "%like%"),
				array("title" => "Renk Adı", "col-name" => "title", "filter-type" => "%like%"),
				array("title" => "Cihaz Rengi?", "col-name" => "cihazrenk", "filter-type" => "in", "filter-value-type" => "i", "input-type" => "select", 
					"value-type" => "array", "select-multiple" => true, "value-data" => array(
						array("text" => "Hayır", "value" => "0"),
						array("text" => "Evet", "value" => "1")
					)
				),
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
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=active&id={colors_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "0"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Pasif Yap", "icon" => '<i class="fa fa-eye-slash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=passive&id={colors_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "1"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Düzenle", "icon" => '<i class="fa fa-edit"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=edit&id={colors_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Sil", "icon" => '<i class="fa fa-trash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=delete&id={colors_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_delete('{type_name}', '{_link}');", "hide-if" => array(
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