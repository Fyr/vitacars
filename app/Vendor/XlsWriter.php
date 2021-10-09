<?php
App::uses('View', 'View');

class XlsWriter {
	private $file, $headers, $csv_div, $enclose, $eol, $charset, $View;

	public function __construct($tplFolder, $options = array()) {
		$this->View = new View();

		$this->tplFolder = $tplFolder;
		$this->charset = (isset($options['charset'])) ? $options['charset'] : 'cp1251';
		$this->file = (isset($options['output']))
			? $options['output']
			: PATH_FILES_UPLOAD.strtolower($this->tplFolder).'.xls';
	}

	public function getFileName() {
		return $this->file;
	}

	public function writeHeader($data = array()) {
		@unlink($this->getFileName());
		$this->writeSection('header', $data);
	}

	public function writeFooter($data = array()) {
		$this->writeSection('footer', $data);
	}

	public function writeDetail($data = array()) {
		$this->writeSection('detail', $data);
	}

	public function writeSection($sectionType, $data = array()) {
		$html = $this->View->element($this->tplFolder.DS.$sectionType, $data);
		file_put_contents($this->file, mb_convert_encoding($html, $this->charset, 'utf-8'), FILE_APPEND);
	}
}
?>