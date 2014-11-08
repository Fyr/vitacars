<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');
class AdminFieldsController extends AdminController {
	public $name = 'AdminFields';
	public $uses = array('Form.FormField', 'Form.PMFormKey');
	
	public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
	
	public function beforeRender() {
		parent::beforeRender();
		$this->set('objectType', 'FormField');
	}
	
	public function index() {
		$this->paginate = array(
			'fields' => array('id', 'field_type', 'label', 'key', 'required', 'exported', 'sort_order'),
			'order' => array('FormField.sort_order' => 'asc')
		);
		$this->PCTableGrid->paginate('FormField');
	}
	
	public function edit($id = 0) {
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('FormField.id', $id);
			}
			$this->request->data('FormField.object_type', 'SubcategoryParam');
			if (Hash::get($this->request->data, 'FormField.formula')) {
				$this->request->data('FormField.options', $this->FormField->packFormulaOptions($this->request->data['FormField']));
			}
			if ($this->FormField->save($this->request->data)) {
				$id = $this->FormField->id;
				if ($this->request->is('post')) {
					$this->PMFormKey->save(array('form_id' => 1, 'field_id' => $id));
				}
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$field = $this->FormField->findById($id);
			$this->request->data = $field;
			if ($this->request->data('FormField.field_type') == FieldTypes::FORMULA) {
				$formField = array_merge(
					$this->request->data('FormField'), 
					$this->FormField->unpackFormulaOptions($this->request->data('FormField.options'))
				);
				$this->request->data('FormField', $formField);
				$this->request->data('FormField.options', '');
			}
		}
		
		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('FormField__SELECT', FieldTypes::SELECT);
		$this->set('FormField__MULTISELECT', FieldTypes::MULTISELECT);
		$this->set('FormField__FORMULA', FieldTypes::FORMULA);
    }
}
