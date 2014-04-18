<?php
App::uses('AdminController', 'Controller');
class AdminProductsController extends AdminController {
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
    	$aParams = $this->FormField->find('all');
    	$aLabels = array();
    	$aFields = array();
    	$hasOne = array();
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
    		}
    	}
    	$this->set('aLabels', $aLabels);
    	$this->Product->bindModel(array('hasOne' => $hasOne), false);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        $aRowset = $this->PCTableGrid->paginate('Product');
        $this->set('aRowset', $aRowset);
        
        $field = $this->FormField->findByLabel('Мотор');
        $this->set('paramMotor', $field);
    }
    
	public function edit($id = 0) {
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
