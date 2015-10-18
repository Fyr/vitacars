<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');
class AdminConstController extends AdminController {
    public $name = 'AdminConst';
    public $components = array('Auth', 'Table.PCTableGrid');
    public $uses = array('Form.PMFormConst');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', 'FormConst');
    }
    
    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'field_type', 'label', 'key', 'value', 'sort_order'),
    		'order' => array('PMFormConst.sort_order' => 'asc')
    	);
    	$this->PCTableGrid->paginate('PMFormConst');
    }
    
    public function edit($id = 0) {
    	if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('PMFormConst.id', $id);
			}
			$this->request->data('PMFormConst.object_type', 'SubcategoryParam');
			
			if ($this->PMFormConst->save($this->request->data)) {
				$id = $this->PMFormConst->id;
				/*
				if ($this->request->is('post')) {
					$this->PMFormKey->save(array('form_id' => 1, 'field_id' => $id));
				}
				*/
				$this->setFlash(__('Be sure to recalculate formulas'));
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$field = $this->PMFormConst->findById($id);
			$this->request->data = $field;
		} else {
			$this->request->data = array('PMFormConst' => array(
				'sort_order' => 1
			));
		}
		
		$this->set('aFieldTypes', FieldTypes::getConstTypes());
    }
}
