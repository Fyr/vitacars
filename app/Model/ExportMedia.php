<?php
App::uses('AppModel', 'Model');
App::uses('Media', 'Media.Model');
class ExportMedia extends Media {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'media';
	public $alias = 'xMedia';
	
	public function setBasePath($path) {
		$this->PHMedia->setBasePath($path);
	}
	
}
