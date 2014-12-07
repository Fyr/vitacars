<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
App::uses('Category', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('Seo', 'Seo.Model');
class Product extends Article {
	const NUM_DETAIL = 5;
	const MOTOR = 6;
	
	public $hasOne = array(
		'Media' => array(
			'className' => 'Media.Media',
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'Product', 'Media.main' => 1),
			'dependent' => true
		),
		'PMFormData' => array(
			'className' => 'Form.PMFormData',
			'foreignKey' => 'object_id',
			'conditions' => array('PMFormData.object_type' => 'ProductParam'),
			'dependent' => true
		),
		'Seo' => array(
			'className' => 'Seo.Seo',
			'foreignKey' => 'object_id',
			'conditions' => array('Seo.object_type' => 'Product'),
			'dependent' => true
		)
	);
	
	public $objectType = 'Product';
	
}
