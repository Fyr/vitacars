<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
App::uses('Category', 'Model');
class Product extends Article {
	const NUM_DETAIL = 5;
	const MOTOR = 6;
	
	public $hasOne = array(
		'Media' => array(
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'Product', 'Media.main' => 1),
			'dependent' => true
		)
	);
	
	public $objectType = 'Product';
	
}
