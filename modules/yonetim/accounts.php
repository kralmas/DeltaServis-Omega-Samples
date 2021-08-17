<?PHP // ŞUTF8 16.09.2019
if (!defined("_P_SEC_CKE_MIX")) { ?>You don't have permission to access this file.<?PHP exit; }

function reload_cache($uidx=0) {
	global $cache;
    $cache->reload(array("code" => "users"));
	$cache->reload(array("code" => "accounts"));
	if ($cache->error) { return array(false, $cache->error_msg); }
	if ($uidx > 0) {
		$cache->reload(array("code" => "auth-user", "params" => array(array("param" => "value0", "value" => $uidx))));
		if ($cache->error) { return array(false, $cache->error_msg); }
	}
	return array(true, '');
}

if (isset($_GET['opt']) AND (($_GET['opt'] == "add" AND $auth->check_alert("ekle")) OR ($_GET['opt'] == "edit" AND $auth->check_alert("duzenle")) OR $_GET['opt'] == "details")) {
	$uid = 0; if ($_GET['opt'] == "edit" OR $_GET['opt'] == "details") { $uid = intval($_GET['id']); }

	// Liste ekranı parametreleri
	//---------------------------------------------------------------------
	$required_list = array(array("dep_id"), array("acc_name"), array("name_surname"), array("status"));
	if ($_GET['opt'] == "add") { $required_list[] = array("acc_pass"); }
	if ($_GET['opt'] == "details") { $required_list = array(); }

	$default_load_options = array(
		"table-name" => _P_DB_PREFIX."ACC_ID",
		"col-list" => array(
			array("col-name" => "dep_id", "input-view" => "Departman", "input-type" => "select", "value-type" => "cache", 
				"value-data" => array(
					"code" => "departments", "label" => "{dep_name}", "value" => "{dep_id}", 
					"order" => array("col-name" => "dep_name", "sort" => "ASC")
				)
			),
			array("col-name" => "cus_id", "input-view" => "Müşteri", "input-type" => "select", "value-type" => "ajax", 
				"value-data" => array(
					"sql" => "SELECT `cus_id` AS value, CONCAT(`title`, ' ( ID: ', `cus_id`, ' )') AS label 
							FROM `"._P_DB_PREFIX."CUS_ID`"
				)
			),
			array("col-name" => "cus_dep_id", "input-view" => "Müşteri Departmanı", "input-type" => "select", "select-default-value" => "1", 
				"value-type" => "cache", "value-data" => array(
					"code" => "cusdepartments", "label" => "{cus_dep_name}", "value" => "{cus_dep_id}", 
					"order" => array("col-name" => "cus_dep_name", "sort" => "ASC")
				)
			),
			array("col-name" => "acc_name", "input-view" => "Kullanıcı Adı", "text-icon" => '<i class="fa fa-user" style="color: black;"></i>'),
			array("col-name" => "acc_pass", "input-view" => "Parola", "text-type" => "password"),
			array("col-name" => "name_surname", "input-view" => "Adı, Soyadı"),
			array("col-name" => "imgurl", "input-view" => "Fotoğraf", "text-type" => "file"),
			array("col-name" => "user_level", "input-view" => "Yetki Seviyesi", "input-type" => "select", "select-default-value" => "0", 
				"select-first-value" => "0", "value-type" => "array", "value-data" => array(
				array("value" => "0", "label" => "Kullanıcı"),
				array("value" => "8", "label" => "Yönetici", "auth" => $auth->check_user_level("8")), 
				array("value" => "9", "label" => "Root", "auth" => $auth->check_user_level("9"))
			)),
			array("col-name" => "status", "input-view" => "Durum", "input-type" => "select", "select-default-value" => "1", 
				"value-type" => "array", "value-data" => array(
				array("value" => "0", "label" => "Pasif"),
				array("value" => "1", "label" => "Aktif"), 
				array("value" => "2", "label" => "Kilitli", "auth" => $auth->check_user_level("9")),
				array("value" => "3", "label" => "Silinmiş", "auth" => $auth->check_user_level("9"))
			))
		),
		"required-list" => $required_list,
		"theme-type" => "file",
		"theme-data" => _P_FLD_MODULES.@$_GET['module']."/template/accounts_add.tpl",
		"upload-dir" => _P_FLD_UPLOAD."accounts/",
	);
	if (($_GET['opt'] == "edit" OR $_GET['opt'] == "details")) {
		$default_load_data_sql = "SELECT * FROM `"._P_DB_PREFIX."ACC_ID` WHERE `acc_id` = '".$uid."'";
		$default_load_options["theme-data"] = _P_FLD_MODULES.@$_GET['module']."/template/accounts_edit.tpl";

		// --------------------------------------------------
		$sql_select = "CONCAT(a.`acc_id`, '-', a.`cus_id`, '-', a.`type`) AS id,
			CONCAT(b.`title`, ' ( ID: ', a.`cus_id`, ' )') AS customer, 
			(CASE WHEN a.`type` = 2 THEN 'İthalatçı' ELSE 'Bayi' END) AS tip,
			a.`acc_id`, a.`cus_id`, a.`type`";

		$sql = "SELECT {SELECTQUERY}
			FROM `"._P_DB_PREFIX."ACC_CUSTOMERS` a
			LEFT JOIN `"._P_DB_PREFIX."CUS_ID` b ON b.`cus_id` = a.`cus_id`";
		
		$where_query = "a.`acc_id` = '".$uid."' AND b.`cus_group` IN (2,5)";

		$options = array(
			"title" => $params_page_title." Listesi",
			"icon" => '<span class="ds-icon font-green">'.$params_module_view_icon.'</span>',
			"db-select" => $sql_select,
			"db-where" => $where_query,
			"db-sql" => $sql,
			"db-orderby" => "customer ASC",
			"link-add-button" => "popup.php?module=".@$_GET['module'].'&view='.@$_GET['view'].'&opt=add',
			"link-add-button-onclick" => "datatable_load_popup('{_link}');",
			"except-cols" => array("acc_id", "cus_id", "type"),
			"search-except" => array(),
			"hidden-cols" => array(),
			"custom-cols" => array(),
			"rename-cols" => array(
				array("id", "ID"),
				array("customer", "Müşteri"),
				array("tip", "Tip")
			),
			"format-cols" => array(),
			"filter-options" => array(
				array("title" => "Müşteri", "col-name" => "cus_id", "filter-type" => "in", "filter-value-type" => "i", 
					"input-type" => "select", "select-multiple" => true, "value-type" => "ajax", "value-data" => array(
						"sql" => "SELECT a.`cus_id` AS value, CONCAT(a.`title`, ' ( ID: ', a.`cus_id`, ' )') AS label 
							FROM `"._P_DB_PREFIX."CUS_ID` a
							WHERE a.`cus_group` IN (2,5)"
					)
				)
			),
			"options" => array(
				"load-fake-user-pass-input" => true,
				"option-show" => true,
				"option-title" => "İşlemler",
				"option-width" => "75px",
				"option-button-title" => "İşlemler",
				"option-button-icon" => '<i class="fa fa-sign-in" aria-hidden="true"></i>',
				"option-style" => "list",
				"option-position" => "left",
				"option-list" => array(
					array("title" => "Sil", "icon" => '<i class="fa fa-trash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=customer-del&id={id}&hide-details=1',
						"onclick" => "datatable_delete('{customer}-{tip}', '{_link}');", "hide-if" => array(
							array("type" => "=", "colname" => "status", "value" => "3"),
							array("type" => "auth", "value" => $auth->check("sil"))
						)
					)
				)
			)
		);

		$options['hide-add-button'] = true;
		$options['hide-filter-button'] = true;
		$options['hide-tools-menu'] = true;
		$options['remove-form-tags'] = true;

		$datatable->load($options);
		$datatable_output = $datatable->output();
		// --------------------------------------------------

		$firm_sql = "SELECT a.`cus_id` AS value, CONCAT(a.`title`, ' ( ID: ', a.`cus_id`, ' )') AS label 
			FROM `"._P_DB_PREFIX."CUS_ID` a
			WHERE a.`cus_group` IN (2,5)";

		$default_load_options["load-custom-values"] = array(
			array("col-name" => "p_firm_sql", "value" => $tools->sifre($firm_sql, true)),
			array("col-name" => "p_datatable_output", "value" => $datatable_output)
		);
	}
	//---------------------------------------------------------------------

	// Kayıt işlemi parametreleri
	//---------------------------------------------------------------------
	if ($_GET['opt'] != "details") {
		$default_save_options = array(
			"post-data" => $_POST,
			"file-data" => $_FILES,
			"upload-dir" => _P_FLD_UPLOAD."accounts/",
			"table-name" => _P_DB_PREFIX."ACC_ID",
			"table-short-name" => "a",
			"col-list" => "*",
			"except-list" => array("acc_id", "remember_me", "login_ip", "login_date", "pass_change_date"),
			"required-list" => $required_list,
			"update-when-has-value" => array("acc_pass", "imgurl"),
			"encrypte" => array(array("col-name" => "acc_pass", "type" => "sha1"))
		);
		if ($_GET['opt'] == "edit" AND $uid != 0) {
			$default_save_options["where"] = "`acc_id` = '".$uid."'";
		}
	}
	//---------------------------------------------------------------------

	// Root yetkisi olmayan üyelerin, silinen, kilitlenen kayıtları görmesini engelliyoruz
	//---------------------------------------------------------------------
	if (($_GET['opt'] == "edit" OR $_GET['opt'] == "details") AND $uid != 0 AND !$auth->check_user_level("9")) {
		$hide_if_dont_have_auth = "SELECT COUNT(`acc_id`) AS total FROM `"._P_DB_PREFIX."ACC_ID` 
				WHERE `acc_id` = '".$uid."' AND `status` IN (0,1)";
	}
	//---------------------------------------------------------------------

	// BU KISMIN ALTINDAKİLERİ DÜZENLEMENİZE GEREK YOK
	//---------------------------------------------------------------------
	//---------------------------------------------------------------------

	// Sayfa başlığı parametreleri
	//---------------------------------------------------------------------
	if ($_GET['opt'] == "add") { $title_opt = "Yeni Kayıt"; }
	elseif ($_GET['opt'] == "edit") { $title_opt = "Kayıt Düzenle, ID: ".$uid; }
	elseif ($_GET['opt'] == "details") { $title_opt = "Kayıt Detayı, ID: ".$uid; }

	$forms->page_options(array(
		"title" => $params_page_title." Düzenle &bull; ".$title_opt,
		"icon" => '<span class="ds-icon font-green" style="margin-right: 5px;">'.$params_module_view_icon.'</span>'
	));
	//---------------------------------------------------------------------

	// Silinen veya Kilitlenen kayıtları, üyelerin görmesini engelliyoruz.
	//---------------------------------------------------------------------
	if (($_GET['opt'] == "edit" OR $_GET['opt'] == "details") AND $uid != 0 AND !$auth->check_user_level("9")) {
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
			$sql_status = $forms->run_sql("insert", $sql_options);
			$show_msg = $sql_status["output"];
		
			if ($sql_status["status"] == true) {
				$uidx = 0;
				$insert_id_count = count($sql_status["success"]);
				if ($insert_id_count > 0) { $uidx = intval($sql_status["success"][0]["insert_id"]); }

				if ($uidx > 0) {
					/*$cusID = $_POST['a--cus_id'];
					$db->params("userID", $uidx, "i");
					$db->params("cusID", $cusID, "i");
					$sql_branch = "INSERT INTO `"._P_DB_PREFIX."ACC_BRANCH` (`user_id`, `branch_id`) VALUES (:userID, :cusID)";
		            $q_branch = $db->query($sql_branch);
            		if ($db->error) { echo __LINE__.'-'.$db->error_msg; }*/

					reload_cache($uidx);
					$logs->add(array("id" => $uidx, "type" => "ekle"));
					if ($logs->error) {	$show_msg .= $logs->error_msg; }
				}
			}
		}
		// -------------------------------------------------------------------------------------

		// Listeleme işlemleri
		// -------------------------------------------------------------------------------------
		$forms->form_options(array("upload-form" => true, "load-fake-user-pass-input" => true));
		//$forms->form_styles(array("file-class" => "filepro"));
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
			$sql_status = $forms->run_sql("update", array($default_save_options));
			if ($forms->error) { echo $forms->error_msg; }
			$show_msg = $sql_status["output"];
		
			if ($sql_status["status"] == true) {
				if (isset($_POST['a--cus_id']) AND $_POST['a--cus_id'] != "1") {
					$db->params("accid", $uid, "i");
					$sql = "DELETE FROM `"._P_DB_PREFIX."ACC_CHATUSERS` WHERE `acc_id` = :accid";
					$s = $db->query($sql);
					if ($db->error) { $show_msg .= $db->error_msg; }
				}

				if (isset($_POST["a--acc_pass_reset"]) AND $_POST["a--acc_pass_reset"] == 1 AND isset($_POST["acc_pass"]) AND !empty($_POST["acc_pass"])) {
					// parola yenileme tarihi resetle
					$db->params("accid", $uid, "i");
					$sql = "UPDATE `"._P_DB_PREFIX."ACC_ID` SET `pass_change_date` = NULL  WHERE `acc_id` = :accid";
					$s = $db->query($sql);
					if ($db->error) { $show_msg .= $db->error_msg; }
				}

				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => $sql_status["success"][0]["changes"]));
				if ($logs->error) {	$show_msg .= $logs->error_msg; }
			}
		}
		// -------------------------------------------------------------------------------------

		// Listeleme işlemleri
		// -------------------------------------------------------------------------------------
		$forms->form_options(array("upload-form" => true, "load-fake-user-pass-input" => true));
		//$forms->form_styles(array("file-class" => "filepro"));
		$default_load_options["load-value-from"] = $default_load_data_sql;
		$forms->load(array($default_load_options));
		if (isset($show_msg) AND !empty($show_msg)) { echo $show_msg; }
		$output = $forms->create();
		echo $output;
		// -------------------------------------------------------------------------------------
	}
	elseif ($_GET['opt'] == "details" AND isset($_GET['id'])) {
		// Listeleme işlemleri
		// -------------------------------------------------------------------------------------
		$forms->form_options(array("load-fake-user-pass-input" => false, "only-show-no-save" => "true"));
		$default_load_options["load-value-from"] = $default_load_data_sql;
		$forms->load(array($default_load_options));
		if (isset($show_msg) AND !empty($show_msg)) { echo $show_msg; }
		$output = $forms->create();
		echo $output;
		// -------------------------------------------------------------------------------------
	}
	else { ?><div class="alert alert-danger"><strong>Hata!</strong> Eksik parametre "id".</div><?PHP }

	if ($_GET["opt"] == "edit" OR $_GET["opt"] == "details") {
		?><script>
		function yonetim_accounts_firma_ekle() {
			var accid = '<?PHP echo $uid; ?>';
			var firmaid = jQuery('#z--firm_list').val();
			var firmatip = jQuery('#z--firm_type').val();
			var id = accid+'-'+firmaid+'-'+firmatip;
			
			datatable_approve('ID:'+id+' Müşteri kaydını hesaba bağlamak istediğinize emin misiniz?', 'popup.php?module=<?PHP echo @$_GET["module"]; ?>&view=<?PHP echo @$_GET["view"]; ?>&opt=customer-add&id='+id+'&hide-details=1');
		}
		</script><?PHP
	}
}
elseif (isset($_GET['opt']) AND $_GET['opt'] == "module" AND $auth->check_alert("duzenle")) {
	if (isset($_GET['id']) AND isset($_GET['depid'])) {
		$uid = intval($_GET['id']);
		$depid = intval($_GET['depid']);

		if (isset($_POST["approve"])) {
			$operation = array(true, "");

			$operation = $auth->save_module(array(
				"auth-type" => "user",
				"user-id" => $uid,
				"department-id" => $uid.','.$depid,
				"data" => $_POST
			));

			if ($operation[0] == false) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $operation[1]; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla düzenlendi..</div><?PHP
				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "duzenle", "notes" => "Module İzni"));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}

		$output = $auth->load_module(array(
			"title" => "Kullanıcılar &bull; Module Düzenle, ID: ".$uid,
			"icon" => '<i class="fa fa-briefcase font-green"></i>',
			"url" => _P_FLE_SCRIPT_NAME.'?module='.@$_GET['module'].'&view='.@$_GET['view'].'&opt=module&id='.$uid.'&depid='.$depid,
			"auth-type" => "user",
			"user-id" => $uid,
			"department-id" => $depid
		));
		echo $output;
	}
	else { ?><div class="alert alert-danger"><strong>Hata!</strong> Eksik parametre "id".</div><?PHP }
}
elseif (isset($_GET['opt']) AND $_GET['opt'] == "auth" AND $auth->check_alert("duzenle")) {
	if (isset($_GET['id'])) {
		$uid = intval($_GET['id']);
		$view_list = array();
		$view_list_count = 0;

		$db->params("uid", $uid, "i");
		$sql = "SELECT a.`view_id`, c.`mod_title`, b.`view_title`
			FROM (SELECT a1.`acc_id`, a1.`view_id` FROM `"._P_DB_PREFIX."ACC_VIEWS` a1
				UNION
				SELECT b2.`acc_id`, a2.`view_id` AS source FROM `"._P_DB_PREFIX."DEP_VIEWS` a2
				LEFT JOIN `"._P_DB_PREFIX."ACC_ID` b2 ON b2.`dep_id` = a2.`dep_id`) AS a
			LEFT JOIN `"._P_DB_PREFIX."VIEW_ID` b ON b.`view_id` = a.`view_id`
			LEFT JOIN `"._P_DB_PREFIX."MODULE_ID` c ON c.`mod_id` = b.`mod_id`
			WHERE a.`acc_id` = :uid
			ORDER BY c.`mod_title` ASC, b.`view_title` ASC";
		$s = $db->query($sql);
		if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
		else {
			$ks = $db->num_rows($s);
			for ($i=0; $i < $ks; $i++) {
				$k = $db->fetch_assoc($s);
				$view_list[] = array("view-id" => $k['view_id']);
			}
			$view_list_count = count($view_list);
		}

		if ($view_list_count > 0) {
			if (isset($_POST["approve"])) {
				$operation = array(true, "");

				$operation = $auth->save(array(
					"auth-type" => "user",
					"user-id" => $uid,
					"data" => $_POST,
					"views" => $view_list
				));

				if ($operation[0] == false) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $operation[1]; ?></div><?PHP }
				else {
					?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla düzenlendi..</div><?PHP 
					reload_cache($uid);
					$logs->add(array("id" => $uid, "type" => "duzenle", "notes" => "Yetki İzni"));
					if ($logs->error) {	echo $logs->error_msg; }
				}
			}

			$output = $auth->load(array(
				"title" => "Kullanıcı &bull; Yetki Düzenle, ID: ".$uid,
				"icon" => '<i class="fa fa-briefcase font-green"></i>',
				"url" => _P_FLE_SCRIPT_NAME.'?module='.@$_GET['module'].'&view='.@$_GET['view'].'&opt=auth&id='.$_GET['id'],
				"auth-type" => "user",
				"user-id" => $uid,
				"views" => $view_list
			));

			echo $output;
		}
		else { ?>Kullanıcı için geçerli bri module->view kaydı mevcut değil.<?PHP }
	}
	else { ?><div class="alert alert-danger"><strong>Hata!</strong> Eksik parametre "id".</div><?PHP }
}
elseif (isset($_GET['opt']) AND $_GET['opt'] == "chatusers" AND $auth->check_alert("duzenle")) {
	if (isset($_GET['id'])) {
		$uid = intval($_GET['id']);
		$uniqkey = date('YmdHis');

		if (isset($_POST["approve"])) {
			$db->params("uid", $uid, "i");
			$sql = "DELETE FROM `"._P_DB_PREFIX."ACC_CHATUSERS` WHERE acc_id = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }

			$customers = array();
			if (isset($_POST['customers']) AND is_array($_POST['customers'])) { $customers = $_POST['customers']; }
			$customers_count = count($customers);

			$sql = "INSERT INTO `"._P_DB_PREFIX."ACC_CHATUSERS` (`acc_id`, `cus_id`) VALUES ";
			for ($i=0; $i < $customers_count; $i++) {
				if ($i > 0) { $sql .= ", "; }
				$sql .= "('".$uid."', '".intval($customers[$i])."')";
			}
			$sql .= ";";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else { ?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla düzenlendi..</div><?PHP
				$cache->reload(array("code" => "acc-chatusers")); }
		}

		?><div class="portlet light bordered">
			<div class="portlet-title">
				<div class="caption font-green">
					<i class="fa fa-address-card font-green"></i>
					<span class="caption-subject bold">Kullanıcılar &bull; Müşteri Düzenle, ID: <?PHP echo $uid; ?></span>
				</div>

				<div class="actions">
					<a href="javascript:;" class="btn green" onClick="jQuery('#form-<?PHP echo $uniqkey; ?>').submit();"> 
						<i class="fa fa-check-square" aria-hidden="true"></i> Kaydet</a> &nbsp;&nbsp;
					<a href="javascript:;" class="btn red" onClick="location.reload();"> 
						<i class="fa fa-refresh" aria-hidden="true"></i> Reset</a>
				</div>
			</div>

			<div class="portlet-body">
				<form name="form-<?PHP echo $uniqkey; ?>" id="form-<?PHP echo $uniqkey; ?>" method="POST" action="<?PHP echo _P_FLE_SCRIPT_NAME; ?>?module=<?PHP echo $_GET['module']; ?>&view=<?PHP echo $_GET['view']; ?>&opt=customers&id=<?PHP echo $uid; ?>" class="ds_form">
					<input type="hidden" name="approve" value="1"><?PHP

					$db->params("uid", $uid, "i");
					$sql = "SELECT a.`acc_id`, a.`acc_name`, a.`name_surname`, a.`cus_id`, b.`title`,
						(SELECT COUNT(1) AS total FROM `"._P_DB_PREFIX."ACC_CHATUSERS` z WHERE z.`acc_id` = :uid AND z.`cus_id` = a.`acc_id`) AS total
						FROM `"._P_DB_PREFIX."ACC_ID` a
						LEFT JOIN `"._P_DB_PREFIX."CUS_ID` b ON b.`cus_id` = a.`cus_id`
						WHERE a.`status` = 1 AND a.`cus_id` != 1
						ORDER BY b.`title` ASC, a.`name_surname` ASC, a.`acc_name` ASC";
					$s = $db->query($sql);
					if ($db->error) { echo $db->error_msg; }
					$ks = $db->num_rows($s);

					$group_control = array();
					?><select multiple="multiple" class="multi-select" id="my_multi_select2" name="customers[]"><?PHP
					for ($i=0; $i < $ks; $i++) {
						$k = $db->fetch_assoc($s);
						if (!in_array($k['cus_id'], $group_control)) {
							$group_control[] = $k['cus_id'];
							?><optgroup label="<?PHP echo $k['title']; ?>"><?PHP
						}

						?><option value="<?PHP echo $k['acc_id']; ?>"<?PHP if ($k['total'] > 0) { ?> selected="selected"<?PHP } ?>>
							<?PHP echo $k['name_surname'].' ( '.$k['acc_name'].' )'; ?></option><?PHP
					}
					?></select><?PHP
				?></form>
			</div>
		</div><?PHP
	}
	else { ?><div class="alert alert-danger"><strong>Hata!</strong> Eksik parametre "id".</div><?PHP }
}
elseif (isset($_GET['opt']) AND $_GET['opt'] == "chatpers" AND $auth->check_alert("duzenle")) {
	if (isset($_GET['id'])) {
		$uid = intval($_GET['id']);
		$uniqkey = date('YmdHis');

		if (isset($_POST["approve"])) {
			$db->params("uid", $uid, "i");
			$sql = "DELETE FROM `"._P_DB_PREFIX."ACC_CHATUSERS` WHERE cus_id = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }

			$personels = array();
			if (isset($_POST['personels']) AND is_array($_POST['personels'])) { $personels = $_POST['personels']; }
			$personels_count = count($personels);

			$sql = "INSERT INTO `"._P_DB_PREFIX."ACC_CHATUSERS` (`acc_id`, `cus_id`) VALUES ";
			for ($i=0; $i < $personels_count; $i++) {
				if ($i > 0) { $sql .= ", "; }
				$sql .= "('".intval($personels[$i])."', '".$uid."')";
			}
			$sql .= ";";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else { ?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla düzenlendi..</div><?PHP 
				$cache->reload(array("code" => "acc-chatusers")); }
		}

		?><div class="portlet light bordered">
			<div class="portlet-title">
				<div class="caption font-green">
					<i class="fa fa-address-card font-green"></i>
					<span class="caption-subject bold">Kullanıcılar &bull; Personel Düzenle, ID: <?PHP echo $uid; ?></span>
				</div>

				<div class="actions">
					<a href="javascript:;" class="btn green" onClick="jQuery('#form-<?PHP echo $uniqkey; ?>').submit();"> 
						<i class="fa fa-check-square" aria-hidden="true"></i> Kaydet</a> &nbsp;&nbsp;
					<a href="javascript:;" class="btn red" onClick="location.reload();"> 
						<i class="fa fa-refresh" aria-hidden="true"></i> Reset</a>
				</div>
			</div>

			<div class="portlet-body">
				<form name="form-<?PHP echo $uniqkey; ?>" id="form-<?PHP echo $uniqkey; ?>" method="POST" action="<?PHP echo _P_FLE_SCRIPT_NAME; ?>?module=<?PHP echo $_GET['module']; ?>&view=<?PHP echo $_GET['view']; ?>&opt=personels&id=<?PHP echo $uid; ?>" class="ds_form">
					<input type="hidden" name="approve" value="1"><?PHP

					$db->params("uid", $uid, "i");
					$sql = "SELECT a.`acc_id`, a.`acc_name`, a.`name_surname`, a.`cus_id`, b.`title`,
						(SELECT COUNT(1) AS total FROM `"._P_DB_PREFIX."ACC_CHATUSERS` z WHERE z.`cus_id` = :uid AND z.`acc_id` = a.`acc_id`) AS total
						FROM `"._P_DB_PREFIX."ACC_ID` a
						LEFT JOIN `"._P_DB_PREFIX."CUS_ID` b ON b.`cus_id` = a.`cus_id`
						WHERE a.`status` = 1 AND a.`cus_id` = 1
						ORDER BY b.`title` ASC, a.`name_surname` ASC, a.`acc_name` ASC";
					$s = $db->query($sql);
					if ($db->error) { echo $db->error_msg; }
					$ks = $db->num_rows($s);

					$group_control = array();
					?><select multiple="multiple" class="multi-select" id="my_multi_select2" name="personels[]"><?PHP
					for ($i=0; $i < $ks; $i++) {
						$k = $db->fetch_assoc($s);
						if (!in_array($k['cus_id'], $group_control)) {
							$group_control[] = $k['cus_id'];
							?><optgroup label="<?PHP echo $k['title']; ?>"><?PHP
						}

						?><option value="<?PHP echo $k['acc_id']; ?>"<?PHP if ($k['total'] > 0) { ?> selected="selected"<?PHP } ?>>
							<?PHP echo $k['name_surname'].' ( '.$k['acc_name'].' )'; ?></option><?PHP
					}
					?></select><?PHP
				?></form>
			</div>
		</div><?PHP
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
			$sql = "UPDATE `"._P_DB_PREFIX."ACC_ID` SET `status` = 0 WHERE `acc_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla pasifleştirildi.</div><?PHP 
				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "0"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
		elseif ($_GET['opt'] == "active" AND isset($_GET['id']) AND $auth->check_alert("duzenle")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."ACC_ID` SET `status` = 1 WHERE `acc_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla aktifleştirildi.</div><?PHP 
				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "duzenle", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "1"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
		elseif ($_GET['opt'] == "delete" AND isset($_GET['id']) AND $auth->check_alert("sil")) {
			$uid = intval($_GET['id']);
			$db->params("uid", $uid, "i");
			$sql = "UPDATE `"._P_DB_PREFIX."ACC_ID` SET `status` = 3 WHERE `acc_id` = :uid";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen kayıt başarıyla silindi.</div><?PHP 
				reload_cache($uid);
				$logs->add(array("id" => $uid, "type" => "sil", "values" => array(array("col-name" => "status", "value-old" => $old_val, "value-new" => "3"))));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
		elseif ($_GET["opt"] == "customer-add" AND isset($_GET['id']) AND $auth->check_alert("ekle")) {
			$uidp = explode("-", $_GET['id']);
			$accid = $uidp[0];
			$cusid = $uidp[1];
			$type = $uidp[2];

			$db->params("accid", $accid, "i");
			$db->params("cusid", $cusid, "i");
			$db->params("type", $type, "i");
			$sql = "SELECT COUNT(1) AS total 
				FROM `"._P_DB_PREFIX."ACC_CUSTOMERS` 
				WHERE `acc_id` = :accid AND `cus_id` = :cusid AND `type` = :type";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				$k = $db->fetch_assoc($s);
				
				if ($k["total"] == 0) {
					$db->params("accid", $accid, "i");
					$db->params("cusid", $cusid, "i");
					$db->params("type", $type, "i");
					$sql = "INSERT INTO `"._P_DB_PREFIX."ACC_CUSTOMERS` (`acc_id`, `cus_id`, `type`) VALUES (:accid, :cusid, :type)";
					$s = $db->query($sql);
					if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
					else {
						?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen müşteri başarıyla eklendi.</div><?PHP 
						$logs->add(array("id" => $_GET['id'], "type" => "ekle", "values" => array()));
						if ($logs->error) {	echo $logs->error_msg; }
					}
				}
				else {
					?><div class="alert alert-danger"><strong>Hata!</strong> Müşteri, hesap üzerinde zaten kayıtlı görünüyor.</div><?PHP
				}
			}
		}
		elseif ($_GET["opt"] == "customer-del" AND isset($_GET['id']) AND $auth->check_alert("sil")) {
			$uidp = explode("-", $_GET['id']);
			$accid = $uidp[0];
			$cusid = $uidp[1];
			$type = $uidp[2];

			$db->params("accid", $accid, "i");
			$db->params("cusid", $cusid, "i");
			$db->params("type", $type, "i");
			$sql = "DELETE FROM `"._P_DB_PREFIX."ACC_CUSTOMERS` WHERE `acc_id` = :accid AND `cus_id` = :cusid AND `type` = :type";
			$s = $db->query($sql);
			if ($db->error) { ?><div class="alert alert-danger"><strong>Hata!</strong> <?PHP echo $db->error_msg; ?></div><?PHP }
			else {
				?><div class="alert alert-success"><strong>Bilgi!</strong> Seçilen müşteri başarıyla kaldırıldı.</div><?PHP 
				$logs->add(array("id" => $_GET['id'], "type" => "sil", "values" => array()));
				if ($logs->error) {	echo $logs->error_msg; }
			}
		}
	}

	if (!isset($_GET['hide-details'])) {
		$sql_select = "a.`acc_id`, a.`dep_id`, b.`dep_name`, CONCAT(SUBSTR(c.`title`,1,25), '...<br>ID: ', a.`cus_id`) AS cus_name, 
			a.`acc_name`, a.`name_surname`, a.`login_ip`, a.`login_date`, a.`pass_change_date`, a.`imgurl`, a.`status`, a.`cus_id`";

		$sql = "SELECT {SELECTQUERY}
			FROM `"._P_DB_PREFIX."ACC_ID` a
			LEFT JOIN `"._P_DB_PREFIX."DEP_ID` b ON b.`dep_id` = a.`dep_id`
			LEFT JOIN `"._P_DB_PREFIX."CUS_ID` c ON c.`cus_id` = a.`cus_id`";
		
		$where_query = "";
		if (!$auth->check_user_level("9")) { $where_query = "a.`status` IN (0,1)"; }

		$options = array(
			"title" => $params_page_title." Listesi",
			"icon" => '<span class="ds-icon font-green">'.$params_module_view_icon.'</span>',
			"db-select" => $sql_select,
			"db-where" => $where_query,
			"db-sql" => $sql,
			"db-orderby" => "a.`name_surname` ASC, a.`acc_name` ASC",
			"link-add-button" => "popup.php?module=".@$_GET['module'].'&view='.@$_GET['view'].'&opt=add',
			"link-add-button-onclick" => "datatable_load_popup('{_link}');",
			"except-cols" => array("dep_id", "cus_id"),
			"search-except" => array("login_date"),
			"hidden-cols" => array("login_ip", "pass_change_date", "kalan_zaman"),
			"custom-cols" => array(
			    array(
			    	"col-title" => "Parola Süresi", "col-format" => "kalan_zaman", "col-data" => array(
			    		"from-date" => "{pass_change_date}", "to-date" => "+90 days", "to-date-status" => "2", "sifir-alti" => "Süre Doldu"
			    	)
				)
			),
			"rename-cols" => array(
				array("acc_id", "ID"),
				array("dep_name", "Departman"),
				array("acc_name", "Kullanıcı"),
				array("name_surname", "Ad, Soyad"),
				array("login_ip", "Giriş Ip"),
				array("login_date", "Son Giriş"),
				array("status", "Durum"),
				array("imgurl", "Resim"),
				array("cus_name", "Müşteri"),
				array("pass_change_date", "Parola Değ. Tar.")
			),
			"format-cols" => array(
				array("col-name" => "status", "format" => "dsstatus"),
				array("col-name" => "login_date", "format" => "datetime"),
				array("col-name" => "pass_change_date", "format" => "datetime"),
				array("col-name" => "imgurl", "format" => "if", "data" => array(
					array("if-type" => "!=", "value" => "", "than" => '<a href="upload/accounts/{_value}" target="_blank"><img src="files.php?img=upload/accounts/{_value}&w=25&h=25&z=1" alt="" style="max-width: 25px; max-height: 25px;"></a>')
				))
			),
			"filter-options" => array(
				array("title" => "Departman", "col-name" => "dep_id", "filter-type" => "in", "filter-value-type" => "i", "input-type" => "select", 
					"select-multiple" => true, "value-type" => "cache", "value-data" => array(
						"code" => "departments", "label" => "{dep_name}", "value" => "{dep_id}", 
						"order" => array("col-name" => "dep_name", "sort" => "ASC")
					)
				),
				array("title" => "Müşteri", "col-name" => "cus_id", "filter-type" => "in", "filter-value-type" => "i", 
					"input-type" => "select", "select-multiple" => true, "value-type" => "ajax", "value-data" => array(
						"sql" => "SELECT `cus_id` AS value, CONCAT(`title`, ' ( ID: ', `cus_id`, ' )') AS label 
							FROM `"._P_DB_PREFIX."CUS_ID`"
					)
				),
				array("title" => "Kullanıcı", "col-name" => "acc_name", "filter-type" => "%like%", "text-icon" => '<i class="fa fa-user font-black"></i>'),
				array("title" => "Ad, Soyad", "col-name" => "name_surname", "filter-type" => "%like%"),
				array("title" => "Son Giriş", "col-name" => "login_date", "filter-type" => "daterange", "input-type" => "daterange"),
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
				"load-fake-user-pass-input" => true,
				"option-show" => true,
				"option-title" => "İşlemler",
				"option-width" => "75px",
				"option-button-title" => "İşlemler",
				"option-button-icon" => '<i class="fa fa-sign-in" aria-hidden="true"></i>',
				"option-style" => "list",
				"option-position" => "left",
				"option-list" => array(
					array("title" => "Aktif Yap", "icon" => '<i class="fa fa-eye"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=active&id={acc_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "0"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Pasif Yap", "icon" => '<i class="fa fa-eye-slash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=passive&id={acc_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_update('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "status", "value" => "1"),
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Yetkiler", "icon" => '<i class="fa fa-lock"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=auth&id={acc_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "auth", "value" => $auth->check("duzenle")),
							array("type" => "auth", "value" => $auth->check_user_level("9"))
						)
					),
					array("title" => "Module", "icon" => '<i class="fa fa-puzzle-piece"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=module&id={acc_id}&depid={dep_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "auth", "value" => $auth->check("duzenle")),
							array("type" => "auth", "value" => $auth->check_user_level("9"))
						)
					),
					array("title" => "Chat Müşteriler", "icon" => '<i class="fa fa-address-card"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=chatusers&id={acc_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "!=", "colname" => "cus_id", "value" => "1"),
							array("type" => "auth", "value" => $auth->check("duzenle")),
							array("type" => "auth", "value" => $auth->check_user_level("9"))
						)
					),
					array("title" => "Chat Personeller", "icon" => '<i class="fa fa-address-card"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=chatpers&id={acc_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "=", "colname" => "cus_id", "value" => "1"),
							array("type" => "auth", "value" => $auth->check("duzenle")),
							array("type" => "auth", "value" => $auth->check_user_level("9"))
						)
					),
					array("title" => "Detaylar", "icon" => '<i class="fa fa-info-circle"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=details&id={acc_id}',
						"onclick" => "datatable_load_popup('{_link}');"
					),
					array("title" => "Düzenle", "icon" => '<i class="fa fa-edit"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=edit&id={acc_id}',
						"onclick" => "datatable_load_popup('{_link}');", "hide-if" => array(
							array("type" => "auth", "value" => $auth->check("duzenle"))
						)
					),
					array("title" => "Sil", "icon" => '<i class="fa fa-trash"></i>', 
						"link" => "popup.php?module=".@$_GET['module']."&view=".@$_GET['view'].'&opt=delete&id={acc_id}&hide-details=1&old_val={status}',
						"onclick" => "datatable_delete('{acc_name}', '{_link}');", "hide-if" => array(
							array("type" => "=", "colname" => "status", "value" => "3"),
							array("type" => "auth", "value" => $auth->check("sil")),
							array("type" => "auth", "value" => $auth->check_user_level("9"))
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