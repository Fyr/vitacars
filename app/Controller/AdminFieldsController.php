<?php
App::uses('AdminController', 'Controller');
class AdminFieldsController extends AdminController {
    public $name = 'AdminFields';
    public $uses = array('Form.FormField');
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', 'FormField');
    }
    
    public function index() {
    	$this->paginate = array(
    		'fields' => array('field_type', 'label', 'fieldset', 'required')
    	);
    	$this->PCTableGrid->paginate('FormField');
    }
    
    public function edit($id = 0) {
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('FormField.id', $id);
			}
			$this->request->data('FormField.object_type', 'SubcategoryParam');
			if ($this->FormField->save($this->request->data)) {
				$id = $this->FormField->id;
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$field = $this->FormField->findById($id);
			$this->request->data = array_merge($this->request->data, $field);
		}
		
		App::uses('FieldTypes', 'Form.Vendor');
		// $this->PHFieldTypes = new FieldTypes();
		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('FormField__SELECT', FieldTypes::SELECT);
    }
}
