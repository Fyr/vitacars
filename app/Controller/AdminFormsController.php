<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');
class AdminFormsController extends AdminController {
	public $name = 'AdminForms';
	public $uses = array('Form.PMFormField', 'Form.PMFormKey', 'Form.PMFormData', 'Form.PMFormConst', 'Task');
	
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
			'fields' => array('id', 'field_type', 'label', 'key', 'required', 'is_price', 'sort_order'),
			'order' => array('PMFormField.sort_order' => 'asc')
		);
		$aRows = $this->PCTableGrid->paginate('PMFormField');
		$this->set('aRows', $aRows);
		$this->set('aTypes', FieldTypes::getTypes());
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
				
				if ($this->request->data('PMFormField.field_type') == FieldTypes::FORMULA) {
					$this->setFlash(__('Be sure to recalculate formulas'));
				}
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$field = $this->PMFormField->findById($id);
			$this->request->data = $field;
		}

		if (!$id) {
			$this->request->data('PMFormField.sort_order', '0');
		}

		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('PMFormField__SELECT', FieldTypes::SELECT);
		$this->set('PMFormField__MULTISELECT', FieldTypes::MULTISELECT);
		$this->set('PMFormField__FORMULA', FieldTypes::FORMULA);
    }
    
    public function recalcFormula() {
		$user_id = AuthComponent::user('id');
		$task = $this->Task->getActiveTask('RecalcFormula', $user_id);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$status = $this->Task->getStatus($id);
			if ($status == Task::DONE) {
				$total = $this->Task->getData($id, 'xdata');
				$this->setFlash(__('%s products have been successfully updated', $total), 'success');
			} elseif ($status == Task::ABORTED) {
				$this->setFlash(__('Processing was aborted by user'), 'error');
			}  elseif ($status == Task::ERROR) {
				$xdata = $this->Task->getData($id, 'xdata');
				$this->setFlash(__('Process execution error! %s', $xdata), 'error');
			}
			if (in_array($status, array(Task::DONE, Task::ABORTED, Task::ERROR))) {
				$this->Task->close($id);
				$this->redirect(array('action' => 'index'));
				return;
			}

			$task = $this->Task->getFullData($id);
		} else {
			if ($this->request->is(array('put', 'post'))) {
				$id = $this->Task->add($user_id, 'RecalcFormula');
				$this->Task->runBkg($id);
				$this->redirect(array('action' => 'recalcFormula'));
				return;
			}
			$this->set('avgTime', $this->Task->avgExecTime('RecalcFormula'));
		}
		$this->set('task', $task);
		/*
		$fields = array('key', 'value');
		$conditions = array('PMFormConst.object_type' => 'SubcategoryParam');
		$aConst = $this->PMFormConst->find('list', compact('fields', 'conditions'));

    	$page = 1;
    	$limit = 1000;
    	$count = 0;
    	$fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
    	while ($rowset = $this->PMFormData->find('all', compact('page', 'limit'))) {
    		$page++;
    		foreach($rowset as $row) {
    			$count++;
    			// $this->PMFormData->recalcFormula($row['PMFormData']['id'], $fields);
				$this->PMFormData->_recalcFormula($row, $fields, $aConst);
			}
    	}
    	$this->Session->setFlash(__('%s products have been updated', $count), 'default', array(), 'success');
    	$this->redirect(array('action' => 'index'));
		*/
    }

}
