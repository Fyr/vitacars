<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('FieldTypes', 'Form.Vendor');
class AdminProductsController extends AdminController {
	
    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.PMFormField', 'Form.PMFormData', 'User', 'Category', 'Subcategory', 'Brand');
    public $helpers = array('ObjectType', 'Form.PHFormFields', 'Form.PHFormData');
    
    private $paramDetail, $aFormula, $aFieldKeys;
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', $this->Product->objectType);
    }
    
    private function _getFieldRights() {
    	$field_rights = AuthComponent::user('field_rights');
    	return ($field_rights) ? explode(',', $field_rights) : array();
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
           	'fields' => array_merge(array('title', 'title_rus', 'detail_num', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        
        $detail_num = '';
        if (isset($this->request->named['Product.detail_num']) && ($detail_num = $this->request->named['Product.detail_num'])) {
        	if ((strpos($detail_num, '*') !== false) || (strpos($detail_num, '~') !== false)) {
        		$lFindSame = (strpos($detail_num, '~') !== false); // поиск похожих
        		$detail_num = str_replace(array('*', '~'), '', $detail_num);
        		$this->set('detail_num', $detail_num);
        		if ($detail_num) {
					$numbers = explode(' ', $detail_num);
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
									$order[] = 'Product.detail_num LIKE "%'.trim($value_).'%" DESC';
								}
							}
							$products = $this->Product->find('all', array('conditions' => array('OR' => $ors)));
							foreach($products as $product) {
								$numbers = array_merge($numbers, explode(' ', $product['Product']['detail_num']));
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
        
        if (!$this->isAdmin()) {
        	if (!$detail_num) {
        		// запретить не-админам показывать полный список
        		$this->paginate['conditions'] = array('0=1');
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

            $this->paginate['conditions'] = array('Product.id' => $aID);
            $this->paginate['order'] = 'FIELD (Product.id, '.$this->request->data('aID').') ASC';
            $aRowset = $this->PCTableGrid->paginate('Product');
            $aRowset = $this->_fillFormula($aRowset);
            $this->set('aRowset', $aRowset);
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
		
		if ($this->request->is(array('post', 'put'))) {
			$formData = $this->request->data('PMFormData');
			// $this->request->data('PMFormData', null);
			unset($this->request->data['PMFormData']);
		}
		
		$fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
		
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$this->PMFormData->saveData($this->request->data, $fields);
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
			$this->request->data('Product.status', array('published', 'active'));
			$this->request->data('Product.count', '0');
			$this->request->data('Product.cat_id', 2133); // category = DEUTZ
			$this->request->data('Product.subcat_id', 2146); // subcategory = DEUTZ 1013
			$this->request->data('Product.brand_id', 2166); // brand = Deutz
		}
	}
	
}
