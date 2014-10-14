<?php
App::uses('AdminController', 'Controller');
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
    		'fields' => array('id', 'field_type', 'label', 'fieldset', 'required', 'exported', 'sort_order'),
    		'order' => array('FormField.sort_order' => 'asc')
    	);
    	$this->PCTableGrid->paginate('FormField');
    }
    
    public function edit($id = 0) {
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('FormField.id', $id);
			}
                        /* Если существует формула, то запишем ее как options */
                        if ($this->request->data('FormField.formula')) {
                            $this->request->data('FormField.options', serialize($this->request->data('FormField.formula')));
                        }

			$this->request->data('FormField.object_type', 'SubcategoryParam');
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
                        if ($field['FormField']['field_type'] == 14) {
                            $field['FormField']['formula'] = unserialize($field['FormField']['options']);
                            $field['FormField']['options'] = '';
                        }
			$this->request->data = array_merge($this->request->data, $field);
		}
		
		App::uses('FieldTypes', 'Form.Vendor');
		// $this->PHFieldTypes = new FieldTypes();
		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('FormField__SELECT', FieldTypes::SELECT);
		$this->set('FormField__MULTISELECT', FieldTypes::MULTISELECT);
		$this->set('FormField__FORMULA', FieldTypes::FORMULA);
    }
}
