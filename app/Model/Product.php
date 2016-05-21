<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Media', 'Media.Model');
App::uses('Category', 'Model');
App::uses('Subcategory', 'Model');
App::uses('Brand', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('Seo', 'Seo.Model');
App::uses('DetailNum', 'Model');
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
	protected $DetailNum;

	public function afterSave($created, $options = array()) {

		$this->loadModel('DetailNum');

		$subcategory = array();
		if (isset($this->data['Product']['subcat_id']) && $this->data['Product']['subcat_id']) {
			$subcategory = $this->Subcategory->findById($this->data['Product']['subcat_id']);
		}
		$category = array();
		if (isset($this->data['Product']['cat_id']) && $this->data['Product']['cat_id']) {
			$category = $this->Category->findById($this->data['Product']['cat_id']);
		}
		$brand = $this->Brand->findById($this->data['Product']['brand_id']);

		$aForm = array('fk_9', 'fk_33', 'fk_34', 'fk_60');
		$aFormData = array();
		foreach($aForm as $e) {
			$aFormData[$e] = '';
			if (isset($this->data['PMFormData']) && $this->data['PMFormData'] && isset($this->data['PMFormData'][$e])) {
				$aFormData[$e] = str_replace(array("\r", "\n"), '', $this->data['PMFormData'][$e]);
			}
		}
		$this->data['Search']['id'] = $this->id;
		$this->data['Search']['body'] = mb_strtolower(implode(',', array(
			$this->data['Product']['code'],
			str_replace(', ', ',', $this->DetailNum->strip($this->data['Product']['detail_num']).','.$this->DetailNum->strip($aFormData['fk_60'])),
			(isset($this->data['Product']['motor'])) ? $this->data['Product']['motor'] : '',
			$aFormData['fk_34'],
			$this->data['Product']['title'],
			$this->data['Product']['title_rus'],
			$aFormData['fk_9'],
			($subcategory) ? $subcategory['Subcategory']['title'] : '',
			($category) ? $category['Category']['title'] : '',
			$brand['Brand']['title'],
			$aFormData['fk_33']
		)));
		$this->Search->save($this->data['Search']);

		$this->loadModel('DetailNum');
		$this->DetailNum->deleteAll(array('product_id' => $this->id));

		$detail_nums = array();
		if ($aFormData['fk_60']) {
			$detail_nums = str_replace(array("\r\n", "\r", "\n"), ',', $this->data['PMFormData']['fk_60']);
			$detail_nums = str_replace(array('   ', '  ', ' '), ',', $detail_nums);
			$detail_nums = explode(',', $detail_nums);
			foreach($detail_nums as $dn) {
				if ($this->DetailNum->isDigitWord($dn)) {
					$dn = $this->DetailNum->strip($dn);
					$data = array('detail_num' => mb_strtolower($dn), 'product_id' => $this->id, 'num_type' => DetailNum::CROSS);
					$this->DetailNum->clear();
					$this->DetailNum->save($data);
				}
			}
		}

		$detail_nums = $this->DetailNum->stripList($this->data['Product']['detail_num']);
		foreach($detail_nums as $dn) {
			$dn = $this->DetailNum->strip($dn);
			if ($this->DetailNum->isDigitWord($dn)) {
				$dn = $this->DetailNum->strip($dn);
				$data = array('detail_num' => mb_strtolower($dn), 'product_id' => $this->id, 'num_type' => DetailNum::ORIG);
				$this->DetailNum->clear();
				$this->DetailNum->save($data);
			}
		}
	}

	public function beforeDelete($cascade = true) {
		$this->loadModel('DetailNum');
		$this->DetailNum->deleteAll(array('product_id' => $this->id));
		return true;
	}
}
