<?PHP // Ş-UTF8, 16.02.2021
// Mehmet Alper Şen

if (!class_exists('ds_language')) {
	class ds_language {
		public $error = false;
		public $error_msg = null;

		private $lang_options = array(
			"folder_lang" => "",
			"lang" => "en"
		);
		private $lang_labels = array();
		private $lang_labels_list = array();

		public $meta_language = "English";
		public $meta_content_language = "en";

		public function __construct($options=array()) {
			if (defined("_P_LCL_LANGUAGE")) { $this->lang_options['lang'] = _P_LCL_LANGUAGE; }
			if (defined("_P_FLD_LANG")) { $this->lang_options['folder_lang'] = _P_FLD_LANG; }
			$this->set($options);
		}

		public function get_version() {
			return array(
				"major" => "1",
				"minor" => "0", 
				"build" =>  "5",
				"status" => "beta",
				"date" => "2021-02-16"
			);
		}

		public function set($options=array()) {
			if (isset($options["lang"])) { $this->lang_options['lang'] = $options["lang"]; }
			if (isset($options["folder_lang"])) { $this->lang_options['folder_lang'] = $options["folder_lang"]; }
		}

		public function load($options=array()) {
			$this->error = false;
			$this->error_msg = null;
			$tmp = array();

			if (isset($options['json']) AND !empty($options['json']) AND file_exists($options['json']))
			$tmp['json_str'] = file_get_contents($options['json']);
			$tmp['json'] = json_decode($tmp['json_str'], true);

			if ($tmp['json'] === null AND json_last_error() !== JSON_ERROR_NONE) {
				$tmp['err_no'] = json_last_error();
				$tmp['err_msg'] = 'Json Error ('.$tmp['err_no'].'): ';
				if ($tmp['err_no'] == JSON_ERROR_DEPTH) {
					$tmp['err_msg'] .= 'Maximum stack depth exceeded';
				}
				elseif ($tmp['err_no'] == JSON_ERROR_STATE_MISMATCH) {
		        	$tmp['err_msg'] .= 'Underflow or the modes mismatch';
		        }
				elseif ($tmp['err_no'] == JSON_ERROR_CTRL_CHAR) {
		        	$tmp['err_msg'] .= 'Unexpected control character found';
		        }
				elseif ($tmp['err_no'] == JSON_ERROR_SYNTAX) {
		        	$tmp['err_msg'] .= 'Syntax error, malformed JSON';
		        }
				elseif ($tmp['err_no'] == JSON_ERROR_UTF8) {
		        	$tmp['err_msg'] .= 'Malformed UTF-8 characters, possibly incorrectly encoded';
		        }
				else {
		        	$tmp['err_msg'] .= 'Unknown error';
		        }

		        $this->error = true;
		        $this->error_msg = $this->show_msg($tmp['err_msg']);
		        return false;
			}

			if (!isset($tmp['json']['labels']) OR (isset($tmp['json']['labels']) AND !is_array($tmp['json']['labels']))) {
				$this->error = true;
				$this->error_msg = $this->show_msg("Wrong Json File.");
				return false;
		    }

			if (isset($tmp['json']['meta-language']) AND !empty($tmp['json']['meta-language'])) {
				$this->meta_language = $tmp['json']['meta-language'];
		    }

			if (isset($tmp['json']['meta-content-language']) AND !empty($tmp['json']['meta-content-language'])) {
				$this->meta_content_language = $tmp['json']['meta-content-language'];
		    }

			$tmp['label_count'] = count($tmp['json']['labels']);

			for ($i=0; $i < $tmp['label_count']; $i++) {
				if (isset($tmp['json']['labels'][$i]['label'])) {
					$lbl = $tmp['json']['labels'][$i]['label'];
					$val = $tmp['json']['labels'][$i]['value'];

					if (!in_array($lbl, $this->lang_labels_list)) {
						$this->lang_labels[$lbl] = $val;
						$this->lang_labels_list[] = $lbl;
					}
				}
			}

			return true;
		}

		public function get($label) {
			$this->error = false;
			$this->error_msg = null;

			if (in_array($label, $this->lang_labels_list)) {
				return $this->lang_labels[$label];
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg("Label ( ".$label." ) Not Found.");
				return $label;
			}
		}

		public function getformat($label, $format=array()) {
			$this->error = false;
			$this->error_msg = null;

			$output = $this->get($label);
			if (strpos($output, '{1}') !== false AND is_array($format)) {
				$format_count = count($format);
				for ($i=0; $i < $format_count; $i++) {
					if (strpos($output, '{'.($i+1).'}') !== false AND isset($format[$i])) {
						$output = str_replace('{'.($i+1).'}', $format[$i], $output);
					}
				}
			}

			return $output;
		}

		public function replace($str, $beginwith="{", $endwith="}") {
			$this->error = false;
			$this->error_msg = null;
			$output = $str;

			if (strpos($output, $beginwith) !== false AND strpos($output, $endwith) !== false) {
				$tmp = array();
				$tmp["ax"] = explode($beginwith, $output);
				$tmp["axc"] = count($tmp["ax"]);
				$tmp['label_count'] = count($this->lang_labels_list);

				for ($i=0; $i < $tmp["axc"]; $i++) {
					$tmp["konum"] = strpos($tmp["ax"][$i], $endwith);
					if ($tmp["konum"] !== false) {
						$tmp["b"] = explode($endwith, $tmp["ax"][$i]);
						$tmp["col"] = $tmp["b"][0];
						if (isset($this->lang_labels[$tmp["col"]])) {
							$output = str_replace($beginwith.$tmp["col"].$endwith, $this->lang_labels[$tmp["col"]], $output);
						}
					}
				}

				unset($tmp);
			}

			return $output;
		}

		private function show_msg($msg="", $type="error") {
			$debug = debug_backtrace();
			$debug_count = count($debug);

			if ($type == "info") { $output = '<div class="alert alert-success"><strong>Bilgi!</strong>: '; }
			else { $output = '<div class="alert alert-danger"><strong>Hata!</strong>: '; }

			if ($msg != "") { $output .= $msg; }
			for ($i=0; $i < $debug_count; $i++) {
				$output .= '<div>';
				for ($x=0; $x < ($i+1); $x++) { $output .= '----'; }
				$output .= '&#9654; <strong>Dosya</strong>: '.$debug[$i]['file'];
				$output .= ', <strong>Satır</strong>: '.$debug[$i]['line'];
				$output .= '</div>';
			}
			$output .= '</div>';
			return $output;
		}
	}
}