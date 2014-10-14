<?php
App::uses('AppModel', 'Model');
App::uses('ExportMedia', 'Model');
class ExportArticle extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'articles';
	public $alias = 'xArticle';
	
	public $hasMany = array(
		'xMedia' => array(
			'className' => 'ExportMedia',
			'foreignKey' => 'object_id',
			'conditions' => array('xMedia.object_type' => 'Article'),
			'dependent' => true
		)
	);
}
