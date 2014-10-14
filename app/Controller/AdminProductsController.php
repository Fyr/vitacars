<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
class AdminProductsController extends AdminController {
	
    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.FormField', 'User', 'Category', 'Subcategory', 'Brand');
    public $helpers = array('ObjectType', 'Form.PHFormFields');
    private $paramDetail;
    
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
        $this->formula = array();
        $this->keys = array();
    	$this->paramDetail = 0;
    	foreach($aParams as $i => $_field) {
    		$i++;
	    	if (!$field_rights || in_array($_field['FormField']['id'], $field_rights)) {
	    		$alias = 'Param'.$i;
                if ($_field['FormField']['key']) {
                    $this->keys = Hash::merge($this->keys, array($alias => $_field['FormField']['key']));
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
				} else if ($_field['FormField']['id'] == Product::NUM_DETAIL) {
					$this->paramDetail = 'Param'.$i;
					$this->set('paramDetail', $this->paramDetail);
				} else if (isset($_field['FormField']['options']) && $_field['FormField']['options'] && $_field['FormField']['field_type'] == 14) {
					$this->formula = Hash::merge($this->formula, array('Param'.$i => unserialize($_field['FormField']['options'])));
				}
    		}
    	}
    	$this->set('aLabels', $aLabels);
    	$this->Product->bindModel(array('hasOne' => $hasOne), false);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        
        if (!$this->isAdmin()) {
        	if (!isset($this->request->named[$this->paramDetail.'.value'])) {
        		$this->request->params['named'][$this->paramDetail.'.value'] = '-';
        	} else {
        		//$number = sprintf('%08d', trim(str_replace('*', '', $this->request->named[$this->paramDetail.'.value'])));
        		//$this->request->params['named'][$this->paramDetail.'.value'] = '*'.$number.'*';
        	}
        }
     
        if (isset($this->request->named[$this->paramDetail.'.value'])) {
            $clear = str_replace('*', '', $this->request->params['named'][$this->paramDetail.'.value']);
            $numbers = explode(' ', $clear);
            $ors = array();
            $order = array();
            foreach ($numbers as $key_ => $value_) {
                if (trim($value_) != ''){
                    $ors[] = array($this->paramDetail.'.value LIKE' => '%'.trim($value_).'%');
                    $order[] = $this->paramDetail.'.value LIKE \'%'.trim($value_).'%\' DESC';
                }
            }
            $this->paginate['conditions'] = array('OR' => $ors);
            $this->paginate['order'] = implode(', ', $order);
            unset($this->request->params['named'][$this->paramDetail.'.value']);
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
            $aRowset = $this->fillFormula($aRowset);
            $this->set('aRowset', $aRowset);
        } else {
            $this->redirect(array('action' => 'index'));
        }
    }

    public function index() {
    	$this->_processParams();

        $aRowset = $this->PCTableGrid->paginate('Product');
        $aRowset = $this->fillFormula($aRowset);
        $this->set('aRowset', $aRowset);

        $field = $this->FormField->findByLabel('Мотор');
        $this->set('motorOptions', $field);
    }
    
    /**
     * Функция заполняет столбцы с типом "Формула"
     */
    private function fillFormula($aRowset) {
        foreach($aRowset as $key => $value) {
            /* Если есть столбец(ы) типа формула */
            if ($this->formula) {
                /* Проходим по каждому столбцу типа формула */
                foreach($this->formula as $key_ => $value_) {
                    /* Разобьем формулу на ключ, знак и цифру */
                    preg_match('/^([A-Z]+[0-9]+) ([\+\-\*\/]) ([0-9]+)$/', $value_, $dataFormula);
                    /* Вычислим значение */
                    foreach ($this->keys as $key__ => $value__) {
                        if ($value__ == $dataFormula[1] && $aRowset[$key][$key__]['value']) {
                            $aRowset[$key][$key__]['value'] = str_replace(',','.',$aRowset[$key][$key__]['value']);
                            if ($dataFormula[2] == '*') {
                                $aRowset[$key][$key_]['value'] = $aRowset[$key][$key__]['value'] * $dataFormula[3];
                            } else if ($dataFormula[2] == '+') {
                                $aRowset[$key][$key_]['value'] = $aRowset[$key][$key__]['value'] + $dataFormula[3];
                            } else if ($dataFormula[2] == '-') {
                                $aRowset[$key][$key_]['value'] = $aRowset[$key][$key__]['value'] - $dataFormula[3];
                            } else if ($dataFormula[2] == '/') {
                                $aRowset[$key][$key_]['value'] = $aRowset[$key][$key__]['value'] / $dataFormula[3];
                            }
                            $aRowset[$key][$key_]['value'] = round($aRowset[$key][$key_]['value'], 2);
			    $aRowset[$key][$key_]['value'] = str_replace(' ', SEPARATOR_DEICHARGE, number_format($aRowset[$key][$key_]['value'], 0, SEPARATOR_DECIMAL, ' '));
                        }
                    }
                }
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
		
		$seo = $this->Seo->getObject($this->Product->objectType, $id);
		if ($seo) {
			$this->request->data('Seo', $seo['Seo']);
		}
		$this->set('aBrandOptions', $this->Brand->getOptions());
	}
}
