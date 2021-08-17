<?PHP // Ş-UTF8, 10.04.2019
// Delta Servis
// Mehmet Alper Şen

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
include_once(_P_TOOLS_MPDF."vendor/autoload.php");

if (!class_exists('ds_pdf')) {
	class ds_pdf {
		public $error = false;
		public $error_msg = null;

		private $content = '<h1>Content Is Empty!</h1>';
		private $encode = 'utf-8';
		private $pdf = '1.5';					// version = 1.4 -> 1.7
		private $orientation = 'P';				// P = Portrait, L = Landscape
		private $use_substitutions = false;		// false (better performance), true = if you have missing caracter issues, its convert fonts
		private $simple_tables = true;			// true (better performance), false = if you need complex table borders
		private $filename = null;
		private $destination = null;
		private $format = array(
			"use" => "text",					// text, resolution
			"text" => 'A4',
			"width" => 0,
			"height" => 0
		);
		private $margins = array(
			"left" => 0, 
			"right" => 0, 
			"top" => 0, 
			"bottom" => 0, 
			"header" => 0, 
			"footer" => 0
		);
		private $options = array(
			"mirrorMargins" => 0,
			"SetHTMLHeader" => array("", false),
			"SetHTMLFooter" => array("", false),
			"SetHTMLHeaderEven" => array("", false),
			"SetHTMLFooterEven" => array("", false)
		);

		public function __construct($options=array()) {
		}

		public function get_version() {
			$this->error = false;
			$this->error_msg = null;

			return array("1", "0", "beta", "build 3", "20190410");
		}

		public function set($options=array()) {
			$this->error = false;
			$this->error_msg = null;

			if (isset($options["content"])) { $this->content = $options["content"]; }
			if (isset($options["encode"])) { $this->encode = $options["encode"]; }
			if (isset($options["pdf"])) { $this->pdf = $options["pdf"]; }
			if (isset($options["orientation"])) { $this->orientation = $options["orientation"]; }
			if (isset($options["use-substitutions"])) { $this->use_substitutions = $options["use-substitutions"]; }
			if (isset($options["simple-tables"])) { $this->simple_tables = $options["simple-tables"]; }
			if (isset($options["format"]) AND is_array($options["format"])) {
				if (isset($options["format"]["use"])) { $this->format["use"] = $options["format"]["use"]; }
				if (isset($options["format"]["text"])) { $this->format["text"] = $options["format"]["text"]; }
				if (isset($options["format"]["width"])) { $this->format["width"] = $options["format"]["width"]; }
				if (isset($options["format"]["height"])) { $this->format["height"] = $options["format"]["height"]; }
			}
			if (isset($options["margins"]) AND is_array($options["margins"])) {
				if (isset($options["margins"]["left"])) { $this->margins["left"] = $options["margins"]["left"]; }
				if (isset($options["margins"]["right"])) { $this->margins["right"] = $options["margins"]["right"]; }
				if (isset($options["margins"]["top"])) { $this->margins["top"] = $options["margins"]["top"]; }
				if (isset($options["margins"]["bottom"])) { $this->margins["bottom"] = $options["margins"]["bottom"]; }
				if (isset($options["margins"]["header"])) { $this->margins["header"] = $options["margins"]["header"]; }
				if (isset($options["margins"]["footer"])) { $this->margins["footer"] = $options["margins"]["footer"]; }
			}
			if (isset($options["options"]) AND is_array($options["options"])) {
				if (isset($options["options"]["mirrorMargins"])) { $this->options["mirrorMargins"] = $options["options"]["mirrorMargins"]; }
				if (isset($options["options"]["SetHTMLHeader"]) AND is_array($options["options"]["SetHTMLHeader"])) {
					if (isset($options["options"]["SetHTMLHeader"][0])) { $this->options["SetHTMLHeader"][0] = $options["options"]["SetHTMLHeader"][0]; }
					if (isset($options["options"]["SetHTMLHeader"][1])) { $this->options["SetHTMLHeader"][1] = $options["options"]["SetHTMLHeader"][1]; }
				}
				if (isset($options["options"]["SetHTMLFooter"]) AND is_array($options["options"]["SetHTMLFooter"])) {
					if (isset($options["options"]["SetHTMLFooter"][0])) { $this->options["SetHTMLFooter"][0] = $options["options"]["SetHTMLFooter"][0]; }
					if (isset($options["options"]["SetHTMLFooter"][1])) { $this->options["SetHTMLFooter"][1] = $options["options"]["SetHTMLFooter"][1]; }
				}
				if (isset($options["options"]["SetHTMLHeaderEven"]) AND is_array($options["options"]["SetHTMLHeaderEven"])) {
					if (isset($options["options"]["SetHTMLHeaderEven"][0])) { $this->options["SetHTMLHeaderEven"][0] = $options["options"]["SetHTMLHeaderEven"][0]; }
					if (isset($options["options"]["SetHTMLHeaderEven"][1])) { $this->options["SetHTMLHeaderEven"][1] = $options["options"]["SetHTMLHeaderEven"][1]; }
				}
				if (isset($options["options"]["SetHTMLFooterEven"]) AND is_array($options["options"]["SetHTMLFooterEven"])) {
					if (isset($options["options"]["SetHTMLFooterEven"][0])) { $this->options["SetHTMLFooterEven"][0] = $options["options"]["SetHTMLFooterEven"][0]; }
					if (isset($options["options"]["SetHTMLFooterEven"][1])) { $this->options["SetHTMLFooterEven"][1] = $options["options"]["SetHTMLFooterEven"][1]; }
				}
			}
		}

		public function clear() {
			$this->error = false;
			$this->error_msg = null;

			$this->content = '<h1>Content Is Empty!</h1>';
			$this->encode = 'utf-8';
			$this->pdf = '1.5';				// version = 1.4 -> 1.7
			$this->orientation = 'P';		// P = Portrait, L = Landscape
			$this->use_substitutions = false;
			$this->simple_tables = true;
			$this->filename = null;
			$this->destination = null;
			$this->format = array(
				"use" => "text",			// text, resolution
				"text" => 'A4',
				"width" => 0,
				"height" => 0
			);
			$this->margins = array(
				"left" => 0, 
				"right" => 0, 
				"top" => 0, 
				"bottom" => 0, 
				"header" => 0, 
				"footer" => 0
			);
			
			return true;
		}

		public function create($options=array()) {
			$this->error = false;
			$this->error_msg = null;

			if ($this->format['use'] == 'resolution') {
				$format = [$this->format['height'], $this->format['width']];
			}
			else {
				$format = $this->format['text'];
			}

			$mpdf = new \Mpdf\Mpdf([
				'mode' => $this->encode, 
				'format' => $format, 
				'orientation' => 'L',
				'margin_left' => $this->margins['left'],
				'margin_right' => $this->margins['right'],
				'margin_top' => $this->margins['top'],
				'margin_bottom' => $this->margins['bottom'],
				'margin_header' => $this->margins['header'],
				'margin_footer' => $this->margins['footer'],
				'mirrorMargins' => $this->options['mirrorMargins']
			]);
			$mpdf->pdf_version = $this->pdf;

			if (!empty($this->options["SetHTMLHeader"][0])) {
				$mpdf->SetHTMLHeader($this->options["SetHTMLHeader"][0], "", $this->options["SetHTMLHeader"][1]);
			}
			if (!empty($this->options["SetHTMLHeaderEven"][0])) {
				$mpdf->SetHTMLHeader($this->options["SetHTMLHeaderEven"][0], "E", $this->options["SetHTMLHeaderEven"][1]);
			}

			if (!empty($this->options["SetHTMLFooter"][0])) {
				$mpdf->SetHTMLFooter($this->options["SetHTMLFooter"][0], "", $this->options["SetHTMLFooter"][1]);
			}
			if (!empty($this->options["SetHTMLFooterEven"][0])) {
				$mpdf->SetHTMLFooter($this->options["SetHTMLFooterEven"][0], "E", $this->options["SetHTMLFooterEven"][1]);
			}

			// performance: https://mpdf.github.io/troubleshooting/slow.html
			$mpdf->useSubstitutions = $this->use_substitutions;
			$mpdf->simpleTables = $this->simple_tables;

			$mpdf->WriteHTML($this->content);

			// details: https://mpdf.github.io/reference/mpdf-functions/output.html
			if ($options['type'] == 'save') {
				// $this->filename, $this->destination
				return $mpdf->Output($this->destination.$this->filename, \Mpdf\Output\Destination::FILE);
			}
			elseif ($options['type'] == 'download') {
				// $this->filename, $this->destination
				return $mpdf->Output($this->filename, \Mpdf\Output\Destination::DOWNLOAD);
			}
			else {
				return $mpdf->Output();
			}
		}

		public function output($options=array()) {
			$this->error = false;
			$this->error_msg = null;

			$options['type'] = 'output';
			return $this->create($options);
		}

		public function save($options=array()) {
			$this->error = false;
			$this->error_msg = null;

			if (!isset($options['filename']) AND !isset($options['destination'])) {
				$this->error = true;
				$this->error_msg = $this->show_msg('Required params "filename" and/or "destination" are empty.');
				return null;
			}
			$this->filename = $options['filename'];
			$this->destination = $options['destination'];

			$options['type'] = 'save';
			return $this->create($options);
		}

		public function download($options=array()) {
			$this->error = false;
			$this->error_msg = null;

			if (!isset($options['filename'])) {
				$this->error = true;
				$this->error_msg = $this->show_msg('Required param "filename" is empty.');
				return null;
			}
			$this->filename = $options['filename'];

			$options['type'] = 'download';
			return $this->create($options);
		}

		private function show_msg($msg="", $type="error") {
			$debug = debug_backtrace();
			if ($type == "info") { $output = '<div class="alert alert-success"><strong>Bilgi!</strong>: '; }
			else { $output = '<div class="alert alert-danger"><strong>Hata!</strong>: '; }
			if ($msg != "") { $output .= $msg; }
			$output .= '<br><strong>Dosya</strong>: '.$debug[0]['file'];
			$output .= '<br><strong>Satır</strong>: '.$debug[0]['line'];
			$output .= '</div>';
			return $output;
		}
	}
}
?>