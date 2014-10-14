<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
class SiteArticle extends Article {
	public $alias = 'SiteArticle';
	
	public $hasMany = array(
		'Media' => array(
			'foreignKey' => 'object_id',
			'dependent' => true
		)
	);
	
}
