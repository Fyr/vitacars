<?php
App::uses('AdminController', 'Controller');
class AdminSettingsController extends AdminController {
    public $name = 'AdminSettings';
    public $uses = array('Settings');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function index() {
        if ($this->request->is(array('post', 'put'))) {
        	$this->request->data('Settings.id', 1);
			$gpz_brands = $this->request->data('Settings.gpz_brands');
			if (is_array($gpz_brands)) {
				$this->request->data('Settings.gpz_brands', implode(',', $gpz_brands));
			}

        	$this->Settings->save($this->request->data);
        	$this->setFlash(__('Settings have been successfully saved'), 'success');
        	$this->redirect(array('action' => 'index'));
        }
        $this->request->data = $this->Settings->getData();
		$this->loadModel('Brand');
		$this->set('aBrandOptions', $this->Brand->find('list', array('order' => 'sorting DESC')));
    }

	public function products() {
	}

	public function updateProducts() {
		$this->loadModel('Task');
		$this->loadModel('Product');

		$task = $this->Task->getActiveTask('ProductDescr', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$status = $this->Task->getStatus($id);

			if ($status == Task::DONE) {
				$aID = $this->Task->getData($id, 'xdata');
				$this->Task->close($id);
				$this->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
			} elseif ($status == Task::ABORTED) {
				$this->setFlash(__('Processing was aborted by user'), 'error');
			}  elseif ($status == Task::ERROR) {
				$xdata = $this->Task->getData($id, 'xdata');
				$this->setFlash(__('Process execution error! %s', $xdata), 'error');
			}
			if (in_array($status, array(Task::DONE, Task::ABORTED, Task::ERROR))) {
				$this->Task->close($id);
				$this->redirect(array('action' => 'updateProducts'));
				return;
			}

			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			if ($this->request->is(array('post', 'put'))) {
				// fdebug($this->request->data);
				$id = $this->Task->add(0, 'ProductDescr', $this->request->data('Filter'));
				$this->Task->runBkg($id);
				$this->redirect(array('action' => 'updateProducts'));
				return;
			}

			$this->loadModel('Brand');
			$this->set('aBrandOptions', $this->Brand->find('list', array('order' => 'sorting ASC')));

			$this->loadModel('Category');
			$this->set('aCategoryOptions', $this->Category->find('list', array('order' => 'sorting ASC')));
		}
	}

}
