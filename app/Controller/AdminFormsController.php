<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');
class AdminFormsController extends AdminController {
	public $name = 'AdminForms';
	public $uses = array('Form.PMFormField', 'Form.PMFormKey', 'Form.PMFormData');
	
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
			'order' => array('PMFormField.sort_order' => 'asc')
		);
		$this->PCTableGrid->paginate('PMFormField');
	}
	
	public function edit($id = 0) {
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('PMFormField.id', $id);
			}
			$this->request->data('PMFormField.object_type', 'SubcategoryParam');
			
			if ($this->PMFormField->save($this->request->data)) {
				$id = $this->PMFormField->id;
				if ($this->request->is('post')) {
					$this->PMFormKey->save(array('form_id' => 1, 'field_id' => $id));
				}
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$field = $this->PMFormField->findById($id);
			$this->request->data = $field;
		}
		
		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('PMFormField__SELECT', FieldTypes::SELECT);
		$this->set('PMFormField__MULTISELECT', FieldTypes::MULTISELECT);
		$this->set('PMFormField__FORMULA', FieldTypes::FORMULA);
    }
    
    public function recalcFormula() {
    	$page = 1;
    	$limit = 10;
    	$count = 0;
    	$fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
    	while ($rowset = $this->PMFormData->find('all', compact('page', 'limit'))) {
    		$page++;
    		foreach($rowset as $row) {
    			$count++;
    			$this->PMFormData->recalcFormula($row['PMFormData']['id'], $fields);
			}
    	}
    	$this->Session->setFlash(__('%s products have been updated', $count), 'default', array(), 'success');
    	$this->redirect(array('action' => 'index'));
    }
}
