<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('FieldTypes', 'Form.Vendor');
class AdminProductsController extends AdminController {
	
    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.PMFormField', 'Form.PMFormData', 'User', 'Category', 'Subcategory', 'Brand', 'ProductRemain');
    public $helpers = array('ObjectType', 'Form.PHFormFields', 'Form.PHFormData');
    
    private $paramDetail, $aFormula, $aFieldKeys;
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', $this->Product->objectType);
    }
    
    private function _processParams() {
        $field_rights = $this->_getFieldRights();
    	$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
    	$this->set('aParams', $aParams);
    	$aLabels = array();
    	$aFields = array();
    	$paramMotor = 0;
    	foreach($aParams as $id => $_field) {
	    	if (!$field_rights || in_array($_field['PMFormField']['id'], $field_rights)) {
	    		$alias = 'PMFormData.fk_'.$id;
				$aFields[] = $alias;
				$aLabels[$alias] = $_field['PMFormField']['label'];
				
				if ($_field['PMFormField']['id'] == Product::MOTOR) {
					$this->set('paramMotor', 'fk_'.$id);
				}
    		}
    	}
    	$this->set('aLabels', $aLabels);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'title_rus', 'detail_num', 'code', 'Category.title', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        
        $detail_num = '';
        if (isset($this->request->named['Product.detail_num']) && ($detail_num = $this->request->named['Product.detail_num'])) {
        	if ((strpos($detail_num, '*') !== false) || (strpos($detail_num, '~') !== false)) {
        		$lFindSame = (strpos($detail_num, '~') !== false); // поиск похожих
        		$detail_num = str_replace(array('*', '~'), '', $detail_num);
        		$this->set('detail_num', $detail_num);
        		if ($detail_num) {
					$numbers = explode(' ', str_replace(',', ' ', $detail_num));
					if ($lFindSame) {
						$ors = array();
						$order = array();
						$i = 0;
						$count = count($numbers);
						$_count = 0;
						while ($i < 100 && $count !== $_count) {
							$i++; // избегать бесконечный цикл
							foreach ($numbers as $key_ => $value_) {
								if (trim($value_) != ''){
									$ors[] = array('Product.detail_num LIKE "%'.trim($value_).'%"');
								}
							}
							$products = $this->Product->find('all', array('conditions' => array('OR' => $ors)));
							foreach($products as $product) {
								$numbers = array_merge($numbers, explode(' ', str_replace(',', ' ', $product['Product']['detail_num'])));
							}
							$numbers = array_unique($numbers);
							$_count = $count;
							$count = count($numbers);
						}
	        		}
						
					$ors = array();
					$order = array();
					foreach ($numbers as $key_ => $value_) {
						if (trim($value_) != ''){
							$ors[] = array('Product.detail_num LIKE "%'.trim($value_).'%"');
							$order[] = 'Product.detail_num LIKE "%'.trim($value_).'%" DESC';
						}
					}
					$this->paginate['conditions'] = array('OR' => $ors);
					$this->paginate['order'] = implode(', ', $order);
        		}
			}
            unset($this->request->params['named']['Product.detail_num']);
        }
        
        if (isset($this->request->named['PMFormData.fk_6']) && $motor = $this->request->named['PMFormData.fk_6']) {
        	$motor = explode(' ', str_replace('*', '', $motor));
        	$ors = array();
        	foreach($motor as $_motor) {
        		$ors[] = 'PMFormData.fk_6 LIKE "%'.$_motor.'%"';
        	}
        	$this->paginate['conditions'][] = array('OR' => $ors);
        	$this->set('motorFilterValue', $motor);
        	unset($this->request->params['named']['PMFormData.fk_6']);
        }
        
        if (!$this->isAdmin()) {
        	if (!$detail_num) {
        		// запретить не-админам показывать полный список
        		$this->paginate['conditions'] = array('0=1');
        	}
        	if ($brand_ids = $this->_getBrandRights()) {
        		$this->paginate['conditions']['Product.brand_id'] = $brand_ids;
        	}
        }
        
        if (isset($this->request->named['Product.id']) && strpos($this->request->named['Product.id'], ',')) {
        	$this->paginate['conditions']['Product.id'] = explode(',', $this->request->named['Product.id']);
        	unset($this->request->params['named']['Product.id']);
        }
    }
    
	public function printXls() {
		if($this->request->data('aID')){
			$this->layout = 'print_xls';
			$this->_processParams();
			$aID = explode(',', $this->request->data('aID'));
			$this->paginate['fields'][] = 'Product.cat_id';
			$this->paginate['fields'][] = 'Product.subcat_id';
			$this->paginate['fields'][] = 'Product.brand_id';
			$this->paginate['conditions'] = array('Product.id' => $aID);
			$this->paginate['order'] = 'FIELD (Product.id, '.$this->request->data('aID').') ASC';
			$this->paginate['limit'] = count($aID);
			$aRowset = $this->PCTableGrid->paginate('Product');
			
			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.cat_id'));
			$conditions = array('Category.object_type' => 'Category', 'Category.id' => $ids);
			$aCategories = $this->Category->find('list', compact('conditions'));
			
			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.subcat_id'));
			$conditions = array('Subcategory.object_type' => 'Subcategory', 'Subcategory.id' => $ids);
			$aSubcategories = $this->Subcategory->find('list', compact('conditions'));
			
			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.brand_id'));
			$conditions = array('Brand.object_type' => 'Brand', 'Brand.id' => $ids);
			$aBrands = $this->Brand->find('list', compact('conditions'));
			
			$this->set(compact('aRowset', 'aCategories', 'aSubcategories', 'aBrands'));
		} else {
			$this->redirect(array('action' => 'index'));
		}
	}

    public function index() {
    	$this->_processParams();

        $aRowset = $this->PCTableGrid->paginate('Product');
        $this->set('aRowset', $aRowset);

        $field = $this->PMFormField->findByLabel('Мотор');
        $this->set('motorOptions', $field);
    }
    
	public function edit($id = 0) {
		if (!$this->isAdmin()) {
			return $this->redirect(array('action' => 'index'));
		}
		if (!$id) {
			// выставляем типы для записей
			$this->request->data('Product.object_type', $this->Product->objectType);
			$this->request->data('Seo.object_type', $this->Product->objectType);
			$this->request->data('PMFormData.object_type', 'ProductParam');
		}
		
		$remain = 0;
		if ($this->request->is(array('post', 'put'))) {
			$this->request->data('PMFormData.fk_6', $this->request->data('Product.motor'));
			
			$a1_val = 0; $a2_val = 0;
			$a1 = 'PMFormData.fk_'.Configure::read('Params.A1');
			$a2 = 'PMFormData.fk_'.Configure::read('Params.A2');
			if ($id) {
				$product = $this->Product->findById($id);
				$a1_val = intval(Hash::get($product, $a1));
				$a2_val = intval(Hash::get($product, $a2));
			}
			$remain = (intval($this->request->data($a1)) - $a1_val) + (intval($this->request->data($a2)) - $a2_val);
		}
		
		$fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
		
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			if ($remain) {
				$product_id = $id;
				$this->ProductRemain->save(compact('product_id', 'remain'));
				
				// скорректировать статистику за год
				$field = 'fk_'.Configure::read(($remain > 0) ? 'Params.incomeY' : 'Params.outcomeY');
				$this->PMFormData->saveField($field, intval($this->PMFormData->field($field)) + $remain); // уже выставлен нужный $this->PMFormData->id
			}
			$this->PMFormData->recalcFormula($this->PMFormData->id, $fields);
			
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		
		$field_rights = $this->_getFieldRights();
		$fieldsAvail = array();
		foreach($fields as $_field) {
			$_field_id = $_field['PMFormField']['id'];
			if ((!$field_rights || in_array($_field_id, $field_rights)) && $_field['PMFormField']['field_type'] != FieldTypes::FORMULA) {
				$fieldsAvail[] = $_field;
				
				if (!$id) {
					if ($_field['PMFormField']['field_type'] == FieldTypes::INT) {
						$this->request->data('PMFormData.fk_'.$_field_id, '0');
					}
					if ($_field['PMFormField']['field_type'] == FieldTypes::FLOAT ) {
						$this->request->data('PMFormData.fk_'.$_field_id, '0.00');
					}
				}
			}
		}
		$this->set('form', $fieldsAvail);
		
		$this->set('aCategories', $this->Category->getOptions('Category'));
		$this->set('aSubcategories', $this->Subcategory->find('all', array(
			'fields' => array('id', 'object_id', 'title', 'Category.id', 'Category.title'),
			'order' => 'object_id'
		)));
		
		$this->set('aBrandOptions', $this->Brand->getOptions());
		
		if (!$id) {
			// выставляем значения по умолчанию
			$this->request->data('Product.status', array('published', 'active', 'show_detailnum'));
			$this->request->data('Product.count', '0');
			$this->request->data('Product.cat_id', 2133); // category = DEUTZ
			$this->request->data('Product.subcat_id', 2146); // subcategory = DEUTZ 1013
			$this->request->data('Product.brand_id', 2166); // brand = Deutz
		}
	}
	
}
