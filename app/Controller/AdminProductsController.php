<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('FieldTypes', 'Form.Vendor');
class AdminProductsController extends AdminController {
	
    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.FormField', 'User', 'Category', 'Subcategory', 'Brand');
    public $helpers = array('ObjectType', 'Form.PHFormFields');
    
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
    	$aParams = $this->FormField->find('all', array('order' => 'sort_order'));
    	$aLabels = array();
    	$aFields = array();
    	$hasOne = array();
        $keys = array();
    	$paramMotor = 0;
        $this->aFormula = array();
        $this->aFieldKeys = array();
    	$this->paramDetail = 0;
    	foreach($aParams as $i => $_field) {
    		$i++;
	    	if (!$field_rights || in_array($_field['FormField']['id'], $field_rights)) {
	    		$alias = 'Param'.$i;
                if ($_field['FormField']['key']) {
                    $this->aFieldKeys[$_field['FormField']['key']] = $alias;
                }
	    		$hasOne[$alias] = array(
					'className' => 'Form.PMFormValue',
					'foreignKey' => 'object_id',
					'conditions' => array($alias.'.field_id' => $_field['FormField']['id'])
				);
				$aFields[] = $alias.'.value';
				$aLabels[$alias.'.value'] = $_field['FormField']['label'];
				
				if ($_field['FormField']['id'] == Product::MOTOR) {
					$paramMotor = 'Param'.$i;
					$this->set('paramMotor', $paramMotor);
				} else if ($_field['FormField']['field_type'] == FieldTypes::FORMULA) {
					$this->aFormula[$alias] = $_field['FormField']['options'];
				}
    		}
    	}
    	$this->set('aLabels', $aLabels);
    	$this->Product->bindModel(array('hasOne' => $hasOne), false);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'title_rus', 'detail_num', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        
        if (!$this->isAdmin()) {
        	if (!(isset($this->request->named['Product.detail_num']) && $this->request->named['Product.detail_num'])) {
        		$this->request->params['named']['Product.detail_num'] = '@'; // запретить искать по всем номерам НЕ-админам
        	} else {
        		//$number = sprintf('%08d', trim(str_replace('*', '', $this->request->named[$this->paramDetail.'.value'])));
        		//$this->request->params['named'][$this->paramDetail.'.value'] = '*'.$number.'*';
        	}
        }
     
        if (isset($this->request->named['Product.detail_num']) && ($detail_num = $this->request->named['Product.detail_num'])) {
        	if ((strpos($detail_num, '*') !== false) || (strpos($detail_num, '~') !== false)) {
        		$lFindSame = (strpos($detail_num, '~') !== false); // поиск похожих
        		$detail_num = str_replace(array('*', '~'), '', $detail_num);
        		$this->set('detail_num', $detail_num);
        		
				$numbers = explode(' ', $detail_num);
				if ($lFindSame) {
					$ors = array();
					$order = array();
					foreach ($numbers as $key_ => $value_) {
						if (trim($value_) != ''){
							$ors[] = array('Product.detail_num LIKE "%'.trim($value_).'%"');
							$order[] = 'Product.detail_num LIKE "%'.trim($value_).'%" DESC';
						}
					}
					$products = $this->Product->find('all', array('conditions' => array('OR' => $ors)));
					$numbers = array();
					foreach($products as $product) {
						$numbers = array_merge($numbers, explode(' ', $product['Product']['detail_num']));
					}
					$number = array_unique($numbers);
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
            unset($this->request->params['named']['Product.detail_num']);
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
        $aRowset = $this->_fillFormula($aRowset);
        $this->set('aRowset', $aRowset);

        $field = $this->FormField->findByLabel('Мотор');
        $this->set('motorOptions', $field);
    }
    
    /**
     * Функция заполняет столбцы с типом "Формула"
     */
    private function _fillFormula($aRowset) {
    	foreach($aRowset as &$row) {
    		// получить данные для вычислений над строкой
    		$aData = array();
    		foreach($this->aFieldKeys as $key => $alias) {
    			$aData[$key] = $row[$alias]['value'];
    		}
    		
    		foreach($this->aFormula as $param => $options) {
    			$row[$param]['value'] = $this->FormField->calcFormula($options, $aData);
    		}
    	}
        return $aRowset;
    }
    
	public function edit($id = 0) {
		if (!$this->isAdmin()) {
			$this->redirect(array('action' => 'index'));
		}
		$this->loadModel('Media.Media');
		$this->loadModel('Seo.Seo');
		if (!$id) {
			$this->request->data('Product.object_type', $this->Product->objectType);
		}
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			if ($this->request->is('put')) {
				// save product params only for updated product
				$this->PMFormValue->saveForm('ProductParam', $id, 1, $this->request->data('PMFormValue'));
			}
			$this->request->data('Seo.object_type', $this->Product->objectType);
			$this->request->data('Seo.object_id', $id);
			$seo = $this->Seo->getObject($this->Product->objectType, $id);
			if ($seo) {
				$this->request->data('Seo.id', $seo['Seo']['id']);
			}
			$this->Seo->save($this->request->data);
			
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		
		$field_rights = $this->_getFieldRights();
		$fields = $this->PMForm->getFields('ProductParams', 1);
		$fieldsAvail = array();
		foreach($fields as $_field) {
			if ((!$field_rights || in_array($_field['FormField']['id'], $field_rights)) && $_field['FormField']['field_type'] != 14) {
				$fieldsAvail[] = $_field;
			}
		}
		$this->set('form', $fieldsAvail);
		$formValues = $this->PMFormValue->getValues('ProductParam', $id);
		// delete space
		foreach ($formValues as $key => $value) {
		    if ($formValues[$key]['PMFormValue']['value'] == '&nbsp;') {
			$formValues[$key]['PMFormValue']['value'] = '';
		    }
		}
		$this->set('formValues', $formValues);
		
		/*
		$subcategories = $this->Subcategory->find('all');
		fdebug($subcategories);
		$aCategoryOptions = array();
		foreach($subcategories as $subcat) {
			$catID = $subcat['Category']['id'];
			$aCategoryOptions[$catID][] = $subcat;
		}
		$this->set('aCategoryOptions', $aCategoryOptions);
		*/
		
		$this->set('aCategories', $this->Category->getOptions('Category'));
		$this->set('aSubcategories', $this->Subcategory->find('all', array(
			'fields' => array('id', 'object_id', 'title', 'Category.id', 'Category.title'),
			'order' => 'object_id'
		)));
		
		if ($id) {
			$seo = $this->Seo->getObject($this->Product->objectType, $id);
			$this->request->data('Seo', Hash::get($seo, 'Seo'));
		}
		$this->set('aBrandOptions', $this->Brand->getOptions());
		
		if (!$id && !$lSaved) {
			// выставляем значения по умолчанию
			$this->request->data('Product.status', array('published', 'active'));
			$this->request->data('Product.count', '0');
			$this->request->data('Product.cat_id', 2133); // category = DEUTZ
			$this->request->data('Product.subcat_id', 2146); // subcategory = DEUTZ 1013
			$this->request->data('Product.brand_id', 2166); // brand = Deutz
		}
	}
}
