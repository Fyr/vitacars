<?php
App::uses('AppController', 'Controller');
App::uses('AdminController', 'Controller');
class IndexController extends AdminController {
	public $name = 'Index';
	public $uses = array();
	
	private $msgFile = '', $count = 0;
	
	public function _afterInit() {
		parent::_afterInit();
		$this->msgFile = '../Locale/'.Configure::read('Config.language').'/LC_MESSAGES/default.po';
	}

	public function _process($fname, $path, $aParams = array()) {
		$file = file_get_contents($path.$fname);
		$msgFile = file_get_contents($this->msgFile);
		preg_match_all('/__\(\'([\w\s\d\%\,\.\!\-]+)\'\)/', $file, $matches);
		if (isset($matches[1])) {
			foreach($matches[1] as $phrase) {
				if (strpos($msgFile, $phrase) === false) {
					// добавить метку в файл
					$this->count++;
					file_put_contents($this->msgFile, 'msgid "'.$phrase.'"'."\r\n".'msgstr "'.Configure::read('Config.language').':'.$phrase.'!"'."\r\n\r\n", FILE_APPEND);
					$msgFile = file_get_contents($this->msgFile);
				}
			}
		}
	}
	
	public function generate() {
		$this->autoRender = false;
		App::uses('Path', 'Core.Vendor');
		
		Path::process(Path::dirContent('../'), array($this, '_process'), true);
		echo $this->count.' label(s) processed';
	}
}
