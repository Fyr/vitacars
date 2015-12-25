<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
App::uses('Category', 'Model');
App::uses('Subcategory', 'Model');
App::uses('Brand', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('Seo', 'Seo.Model');
class Product extends Article {
	const NUM_DETAIL = 5;
	const MOTOR = 6;
	
	public $belongsTo = array(
		'Category' => array(
			'foreignKey' => 'cat_id'
		),
		'Subcategory' => array(
			'foreignKey' => 'subcat_id'
		),
		'Brand' => array(
			'foreignKey' => 'brand_id'
		)
	);
	
	public $hasOne = array(
		'Media' => array(
			'className' => 'Media.Media',
			'foreignKey' => 'object_id',
			'conditions' => array('Media.media_type' => 'image', 'Media.object_type' => 'Product', 'Media.main' => 1),
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
		),
		'Search' => array(
			'foreignKey' => 'id',
			'dependent' => true
		)
	);
	
	public $objectType = 'Product';

	public function afterSave($created, $options = array()) {
		$subcategory = $this->Subcategory->findById($this->data['Product']['subcat_id']);
		$brand = $this->Brand->findById($this->data['Product']['brand_id']);
		$this->data['Search']['id'] = $this->id;
		$this->data['Search']['body'] = implode(',', array(
			$this->data['Product']['code'],
			str_replace(', ', ',', $this->data['Product']['detail_num']),
			$this->data['Product']['title'],
			$this->data['Product']['title_rus'],
			$this->data['Product']['motor'],
			$subcategory['Subcategory']['title'],
			$subcategory['Category']['title'],
			$brand['Brand']['title']
		));
		$this->Search->save($this->data['Search']);
	}
}
