<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
class Subcategory extends Article {
	
	var $belongsTo = array(
		'Category' => array(
			'foreignKey' => 'object_id',
			'dependent' => true
		)
	);

	protected $objectType = 'Subcategory';
	
	/*
	public $belongsTo = array(
		'className' => 'Category',
		'foreing'
	);
	*/
}
