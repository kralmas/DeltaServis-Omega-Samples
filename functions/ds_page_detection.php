<?PHP // ŞUTF-8, 15.10.2018
if (!function_exists("ds_page_detection")) {
	function ds_page_detection($options = array()) {
		global $_GET;

		$output = array(
			"params_module_title" => "Hesap İşlemleri",
			"params_page_title" => "Login",
			"params_module_dir" => "hesap-islemleri",
			"params_module_view" => "login",
			"params_module_view_icon" => "",
			"params_module_id" => 0,
			"params_module_view_id" => 0,
			"params_load_wrappers" => false
		);

		$tmp = array();

		$tmp["cache"] = null;
		if (isset($options["cache"])) { $tmp["cache"] = $options["cache"]; }

		$tmp["pdmode"] = "default";
		if (isset($options["pdmode"]) AND !empty($options["pdmode"])) {
			$tmp["pdmode"] = $options["pdmode"];
		}

		$tmp["apikey_secret"] = "_ufukalper260864*";
		$tmp["apikey"] = "";
		if (isset($options["apikey"]) AND !empty($options["apikey"])) {
			$tmp["apikey"] = $options["apikey"];
		}

		if (isset($_SESSION["user_id"]) OR $tmp["apikey"] == $tmp["apikey_secret"]) {
			if (isset($_GET["module"]) AND (isset($_GET['view']) OR $tmp["pdmode"] == "ajax")) {
				$tmp["modules_list"] = $tmp["cache"]->get(array("code" => "modules"));
				if ($tmp["cache"]->error) { echo $tmp["cache"]->error_msg; }
				$tmp["modules_count"] = count($tmp["modules_list"]);

				$output["params_module_dir"] = str_replace(array("/", "\\", "?", ".."), "", $_GET["module"]);
				if ($tmp["pdmode"] == "ajax") { $output["params_module_view"] = ""; }
				else { $output["params_module_view"] = str_replace(array("/", "\\", "?", ".."), "", $_GET["view"]); }
				$output["params_load_wrappers"] = true;
				$tmp["params_get_status"] = false;

				for ($i=0; $i < $tmp["modules_count"]; $i++) {
					if (isset($tmp["modules_list"][$i]) AND is_array($tmp["modules_list"][$i]) AND $tmp["modules_list"][$i]['status'] == "1" AND $tmp["modules_list"][$i]['mod_title'] != "" AND 
						$tmp["modules_list"][$i]['mod_dir'] == $output["params_module_dir"]) {
						$output["params_module_title"] = $tmp["modules_list"][$i]['mod_title'];
						if ($tmp["pdmode"] == "ajax") {
							$tmp["params_get_status"] = true;
							$output["params_module_id"] = $tmp["modules_list"][$i]['mod_id'];
							$output["params_page_title"] = @$_GET['ajax'].' - '.$output["params_module_title"];
						}
						else {
							$tmp["views_list"] = $tmp["cache"]->get(array("code" => "views"));
							if ($tmp["cache"]->error) { echo $tmp["cache"]->error_msg; }
							$tmp["views_count"] = count($tmp["views_list"]);

							for ($x=0; $x < $tmp["views_count"]; $x++) {
								if (isset($tmp["views_list"][$x]) AND is_array($tmp["views_list"][$x]) AND $tmp["views_list"][$x]['status'] == "1" AND $tmp["views_list"][$x]['view_title'] != "") {
									if ($tmp["views_list"][$x]['mod_id'] == $tmp["modules_list"][$i]['mod_id'] AND $tmp["views_list"][$x]['view_file'] == $output["params_module_view"]) {
										$output["params_page_title"] = $tmp["views_list"][$x]['view_title'];
										$output["params_module_view"] = $tmp["views_list"][$x]['view_file'];
										$output["params_module_id"] = $tmp["views_list"][$x]['mod_id'];
										$output["params_module_view_id"] = $tmp["views_list"][$x]['view_id'];
										$output["params_module_view_icon"] = $tmp["views_list"][$x]['view_icon'];
										$tmp["params_get_status"] = true;
										Break;
									}
								}
							}
						}
						Break;
					}
				}

				if ($tmp["params_get_status"] == false) {
					if ($tmp["pdmode"] == "ajax") {
						$output["params_module_dir"] = "error";
						$output["params_module_view"] = "404";
						$output["params_module_title"] = "Error";
						$output["params_page_title"] = "404";
					}
					else {
						if ($_SESSION["user_level"] == 9 AND $output["params_module_dir"] == "yonetim" AND ($output["params_module_view"] == "modules" OR $output["params_module_view"] == "modules-views")) {
							$output["params_module_title"] = "Root Access";
							$output["params_page_title"] = "Module Yönetimi";
						}
						else {
							$output["params_module_dir"] = "error";
							$output["params_module_view"] = "404";
							$output["params_module_title"] = "Error";
							$output["params_page_title"] = "404";
						}
					}
				}
				else if ($output["params_module_dir"] == "hesap-islemleri" AND $output["params_module_view"] == "logout") {
					$output["params_load_wrappers"] = false;
				}
			}
			else {
				$output["params_module_dir"] = "dashboard";
				$output["params_module_view"] = "dashboard";
				$output["params_load_wrappers"] = true;
				$output["params_module_title"] = "Anasayfa";
				$output["params_page_title"] = "Dashboard";
			}
		}

		$get_count = count($_GET);
		$get_keys = array_keys($_GET);
		for ($i=0; $i < $get_count; $i++) {
			if (strpos($output["params_page_title"], '{_get_'.$get_keys[$i].'}') !== false) {
				$output["params_page_title"] = str_replace('{_get_'.$get_keys[$i].'}', $_GET[$get_keys[$i]], $output["params_page_title"]);
			}
		}

		//unset($options);
		unset($tmp);

		return $output;
	}
}
?>