<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
App::uses('Category', 'Model');
class Product extends Article {
	
	public $belongsTo = array(
		'Category' => array(
			'foreignKey' => 'cat_id'
		),
		'Subcategory' => array(
			'foreignKey' => 'subcat_id'
		),
	);
	public $hasOne = array(
		'Media' => array(
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'Product', 'Media.main' => 1),
			'dependent' => true
		)
	);
	
	public $objectType = 'Product';
	
}
