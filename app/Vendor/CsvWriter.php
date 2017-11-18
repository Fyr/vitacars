<?php
class CsvWriter {
	private $file, $headers, $csv_div, $enclose, $eol, $charset;

	public function __construct($file, $headers, $options = array()) {
		$this->file = $file;
		$this->headers = $headers;
		$this->csv_div = (isset($options['csv_div'])) ? $options['csv_div'] : ';';
		$this->enclose = (isset($options['enclose'])) ? $options['enclose'] : '"';
		$this->eol = (isset($options['eol'])) ? $options['eol'] : "\r\n";
		$this->charset = (isset($options['charset'])) ? $options['charset'] : 'cp1251';
	}

	public function writeHeaders() {
		@unlink($this->file);
		$this->writeLn(implode($this->csv_div, $this->headers));
	}

	private function _preprocess($data) {
		$_data = array();
		foreach($this->headers as $key) {
			if (isset($data[$key])) {
				$s = str_replace($this->enclose, '\\'.$this->enclose, $data[$key]);
				if (strpos($s, ' ') !== false) {
					$s = $this->enclose.$s.$this->enclose;
				}
				$_data[] = $s;
			}
		}
		return implode($this->csv_div, $_data);
	}

	public function writeLn($str) {
		file_put_contents($this->file, mb_convert_encoding($str, $this->charset, 'utf-8').$this->eol, FILE_APPEND);
	}

	public function writeData($data) {
		$this->writeLn($this->_preprocess($data));
	}
}
?>