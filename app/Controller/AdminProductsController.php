<?php
App::uses('AdminController', 'Controller');
class AdminProductsController extends AdminController {
	const NUM_DETAIL = 5;
	const MOTOR = 6;
	
    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.FormField', 'User');
    public $helpers = array('ObjectType', 'Form.PHFormFields');
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', $this->Product->objectType);
    }
    
    private function _getFieldRights() {
    	$field_rights = AuthComponent::user('field_rights');
    	return ($field_rights) ? explode(',', $field_rights) : array();
    }
    
    public function index() {
    	$field_rights = $this->_getFieldRights();
    	$aParams = $this->FormField->find('all', array('order' => 'sort_order'));
    	$aLabels = array();
    	$aFields = array();
    	$hasOne = array();
    	$paramMotor = 0;
    	$paramDetail = 0;
    	foreach($aParams as $i => $_field) {
    		$i++;
	    	if (!$field_rights || in_array($_field['FormField']['id'], $field_rights)) {
	    		$alias = 'Param'.$i;
	    		$hasOne[$alias] = array(
					'className' => 'Form.PMFormValue',
					'foreignKey' => 'object_id',
					'conditions' => array($alias.'.field_id' => $_field['FormField']['id'])
				);
				$aFields[] = $alias.'.value';
				$aLabels[$alias.'.value'] = $_field['FormField']['label'];
				
				if ($_field['FormField']['id'] == self::MOTOR) {
					$paramMotor = 'Param'.$i;
					$this->set('paramMotor', $paramMotor);
				} else if ($_field['FormField']['id'] == self::NUM_DETAIL) {
					$paramDetail = 'Param'.$i;
					$this->set('paramDetail', $paramDetail);
				}
    		}
    	}
    	$this->set('aLabels', $aLabels);
    	$this->Product->bindModel(array('hasOne' => $hasOne), false);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext', 'count'), $aFields)
        );

        if (!$this->isAdmin()) {
        	if (!isset($this->request->named[$paramDetail.'.value'])) {
        		$this->request->params['named'][$paramDetail.'.value'] = '-';
        	} else {
        		//$number = sprintf('%08d', trim(str_replace('*', '', $this->request->named[$paramDetail.'.value'])));
        		//$this->request->params['named'][$paramDetail.'.value'] = '*'.$number.'*';
        	}
        }
     
        if (isset($this->request->named[$paramDetail.'.value'])) {
            $clear = str_replace('*', '', $this->request->params['named'][$paramDetail.'.value']);
            $numbers = explode(' ', $clear);
            $ors = array();
            $order = array();
            foreach ($numbers as $key_ => $value_) {
                if (trim($value_) != ''){
                    $ors[] = array($paramDetail.'.value LIKE' => '%'.trim($value_).'%');
                    $order[$paramDetail.'.value LIKE %'.trim($value_).'%'] = 'DESC';
                }
            }
            $this->paginate['conditions'] = array('OR' => $ors);
            $this->paginate['order'] = $order;
            unset($this->request->params['named'][$paramDetail.'.value']);
        }
        $aRowset = $this->PCTableGrid->paginate('Product');
        $this->set('aRowset', $aRowset);

        $field = $this->FormField->findByLabel('Мотор');
        $this->set('motorOptions', $field);
    }
    
	public function edit($id = 0) {
		if (!$this->isAdmin()) {
			$this->redirect(array('action' => 'index'));
		}
		$this->loadModel('Media.Media');
		if (!$id) {
			$this->request->data('Product.object_type', $this->Product->objectType);
		}
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			if ($this->request->is('put')) {
				// save product params only for updated product
				$this->PMFormValue->saveForm('ProductParam', $id, 1, $this->request->data('PMFormValue'));
			}
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		
		$field_rights = $this->_getFieldRights();
		$fields = $this->PMForm->getFields('ProductParams', 1);
		$fieldsAvail = array();
		foreach($fields as $_field) {
			if (!$field_rights || in_array($_field['FormField']['id'], $field_rights)) {
				$fieldsAvail[] = $_field;
			}
		}
		$this->set('form', $fieldsAvail);
		$this->set('formValues', $this->PMFormValue->getValues('ProductParam', $id));
	}
}
