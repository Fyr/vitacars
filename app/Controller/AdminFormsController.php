<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');

// App::uses('Currency', 'Model');
class AdminFormsController extends AdminController {
	public $name = 'AdminForms';
	public $uses = array('Form.PMFormField', 'Form.PMFormKey', 'Form.PMFormData', 'Form.PMFormConst', 'Task', 'Currency');
	
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
			'fields' => array('id', 'field_type', 'label', 'key', 'required', 'exported', 'is_price', 'is_stock', 'sort_order'),
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
			$this->request->data('PMFormField.options', $this->PMFormField->packOptions($this->request->data('PMFormField')));
			
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
			$this->request->data = $this->PMFormField->findById($id);
			$this->request->data('PMFormField', $this->PMFormField->unpackOptions($this->request->data('PMFormField')));
		} else {
			$this->request->data('PMFormField.sort_order', '0');
			$this->request->data('PMFormField.decimals', '2');
			$this->request->data('PMFormField.div_float', '.');
			$this->request->data('PMFormField.div_int', ',');
			$this->request->data('PMFormField.price_decimals', '2');
			$this->request->data('PMFormField.price_div_float', '.');
			$this->request->data('PMFormField.price_div_int', ',');
		}

		$this->set('aFieldTypes', FieldTypes::getTypes());
		$this->set('aCurrency', $this->Currency->getOptions());
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

    }

}
