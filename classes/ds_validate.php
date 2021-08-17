<?PHP // Ş-UTF8, 28.11.2016
// Delta Servis
// Mehmet Alper Şen

if (!class_exists('ds_validate')) {
	class ds_validate {
		private $check_key = null;
		private $check_value = null;
		private $add_param = null;
		private $unicode = true;
		private $output = true;

	    public function get_version() {
	    	return array("1", "0", "beta", "build 1", "20161128");
	    }

		public function check($check_key, $check_value, $add_param="", $unicode=true, $xoutput=true) {
			// $check_key = numeric, alpha, alpha_numeric, phone, creditcard, ip
			// $add_param = if you want to add extra caracters to regex, use this parameter (does not work with format control)
			// $unicode = if true, for alpha and alpha_numeric, it will check unicode letters (like öçşığü) too

			$this->check_key = $check_key;
			$this->check_value = $check_value;
			$add_param = str_replace(
				array("/", "\\"),
				array("\\/", "\\\\"),
				$add_param);
			$this->add_param = $add_param;
			$this->unicode = $unicode;
			$this->output = $xoutput;

			// Level 1 : Checking illegal caracters, and creating fixed output
			$chk = $this->check_caracters();
			$status_value = $chk[0];
			$output_value = $chk[1];
			$illegal_chars = $chk[2];

			// Level 2 : Checking wrong format
			if ($status_value) {
				$chk = $this->check_format();
				$status_format = $chk[0];
				$output_format = $chk[1];
			}
			else {
				$status_format = false;
				$output_format = "";
			}

			$output = array("status_value" => $status_value, "status_format" => $status_format, "output_value" => $output_value, "output_format" => $output_format, "illegal_chars" => $illegal_chars);
			return $output;
		}

		private function check_caracters() {
			$status_value = true;
			$output_value = $this->check_value;
			$tmp_regex = "/[^0-9]+/";

			if ($this->check_key == "numeric") {
				$tmp_regex = "/[^0-9";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "alpha") {
				$tmp_regex = "/[^";
					if ($this->unicode) { $tmp_regex .= "\pL"; } else { $tmp_regex .= "a-zA-Z"; }
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "alpha_numeric") {
				$tmp_regex = "/[^";
					if ($this->unicode) { $tmp_regex .= "\pL0-9"; } else { $tmp_regex .= "a-zA-Z0-9"; }
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "phone") {
				$tmp_regex = "/[^0-9\(\)\.\+\s\-\/\*#extnsion";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "url") {
				$tmp_regex = "/[^a-zA-Z0-9:\/\-_\.\?\+=&%#!,;:|~";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "email") {
				$tmp_regex = "/[^a-zA-Z0-9\-_@\.";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "creditcard") {
				$tmp_regex = "/[^0-9\-\s";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}
			else if ($this->check_key == "ip") {
				$tmp_regex = "/[^0-9\.";
					if (!empty($this->add_param)) { $tmp_regex .= $this->add_param; }
					$tmp_regex .= "]+/";
					if ($this->unicode) { $tmp_regex .= "u"; }
			}

			$illegal_characters = array(array());
			if (preg_match_all($tmp_regex, $this->check_value, $illegals)) {
				$status_value = false;
				$illegal_characters = $illegals;
			}

			if ($this->output) {
				if (!$status_value) {
					$output_value = preg_replace($tmp_regex, "", $output_value);
				}
			}

			return array($status_value, $output_value, $illegal_characters);
		}

		private function check_format() {
			$status_format = true;
			$output_format = "";

			if ($this->check_key == "phone") {
				$tmp_regex = "/^\s*(?:\+?(\d{1,3}))?([-. (]*(\d{3})[-. )]*)?((\d{3})[-. ]*(\d{2,4})(?:[-.x ]*(\d+))?)\s*$/m";
				if (!preg_match($tmp_regex, $this->check_value)) {
					$status_format = false;
				}
			}
			else if ($this->check_key == "url") {
				$tmp_regex = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
				if (!preg_match($tmp_regex, $this->check_value)) {
					$status_format = false;
				}
			}
			else if ($this->check_key == "email") {
				if (!filter_var($this->check_value, FILTER_VALIDATE_EMAIL)) {
					$status_format = false;
				}
			}
			else if ($this->check_key == "creditcard") {
				// Supported only mastercard and visa
				$ccno = preg_replace("/\D/", "", $this->check_value);
				$cclength = strlen($ccno);
				$parity=$cclength % 2;

				if ($cclength == 13 OR $cclength == 16) {
					$total = 0;
					for ($i=0; $i<$cclength; $i++) {
						$digit = $ccno[$i];
						if ($i % 2 == $parity) {
							$digit *= 2;
							if ($digit > 9) {
								$digit -= 9;
							}
						}
						$total += $digit;
					}

					if ($total % 10 != 0) { $status_format = false; }
					else {
						if ($ccno[0] == 5 AND $cclength == 16) { $output_format = "MASTER"; }
						else if ($ccno[0] == 4) { $output_format = "VISA"; }
						else { $output_format = "UNKNOWN"; }
					}
				}
				else { $status_format = false; }
			}
			else if ($this->check_key == "ip") {
				if (!filter_var($this->check_value, FILTER_VALIDATE_IP)) {
					$status_format = false;
				}
			}

			return array($status_format, $output_format);
		}
	}
}
?>