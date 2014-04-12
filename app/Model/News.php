<?php
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
class News extends Article {
	
	var $hasOne = array(
		'Media' => array(
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'News', 'Media.main' => 1),
			'dependent' => true
		)
	);
	
	protected $objectType = 'News';
}
