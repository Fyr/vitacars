<?php
App::uses('AppModel', 'Model');
App::uses('Media', 'Media.Model');
class ExportMedia extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'media_products';
	public $alias = 'xMedia';
	
}
