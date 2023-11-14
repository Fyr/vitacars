<?php
App::uses('AppController', 'Controller');
class AdminController extends AppController {
	public $name = 'Admin';
	public $layout = 'admin';
	// public $components = array();
	public $uses = array();

	public function _beforeInit() {
	    // auto-add included modules - did not included if child controller extends AdminController
	    $this->components = array_merge(array('Auth', 'Core.PCAuth', 'Table.PCTableGrid'), $this->components);
	    $this->helpers = array_merge(array('Html', 'Table.PHTableGrid', 'Form.PHForm'), $this->helpers);
	    
		$this->aNavBar = array(
			// 'Page' => array('label' => __('Static Pages'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'Page')),
			// 'News' => array('label' => __('News'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'News')),
			'Products' => array('label' => __('Products'), 'href' => '', 'submenu' => array(
				'Category' => array('label' => __('Categories'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'Category')),
				'Brands' => array('label' => __('Brands'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'Brand')),
				'Forms' => array('label' => __('Tech.params'), 'href' => array('controller' => 'AdminForms', 'action' => 'index')),
				'Constants' => array('label' => __('Constants'), 'href' => array('controller' => 'AdminConst', 'action' => 'index')),
				'Products' => array('label' => __('Products'), 'href' => array('controller' => 'AdminProducts', 'action' => 'index')),
				// 'Settings' => array('label' => __('Product Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index'))
			)),
			'Tasks' => array('label' => __('Bkg.tasks'), 'href' => '', 'submenu' => array(
				array('label' => __('Upload counters'), 'href' => array('controller' => 'AdminTasks', 'action' => 'task', 'UploadCounters')),
				array('label' => __('Upload new products'), 'href' => array('controller' => 'AdminTasks', 'action' => 'task', 'UploadNewProducts')),
				array('label' => __('Update products'), 'href' => array('controller' => 'AdminTasks', 'action' => 'task', 'ProductDescr')),
				array('label' => __('Check products'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'checkProducts')),
			)),
			'Orders' => array('label' => __('Orders'), 'href' => '', 'submenu' => array(
				array('label' => __('Orders'), 'href' => array('controller' => 'AdminOrders', 'action' => 'index')),
				array('label' => __('Agents'), 'href' => array('controller' => 'AdminAgents', 'action' => 'index')),
			)),
			'Reports' => array('label' => __('Reports'), 'href' => '', 'submenu' => array(
				array('label' => __('Sales by period'), 'href' => array('controller' => 'AdminReports', 'action' => 'sales')),
				array('label' => __('Search by period'), 'href' => array('controller' => 'AdminReports', 'action' => 'search')),
			)),
			// 'slider' => array('label' => __('Slider'), 'href' => array('controller' => 'AdminSlider', 'action' => 'index')),
			// 'settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index'))
			'System' => array('label' => __('System'), 'href' => '', 'submenu' => array(
				'Settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index')),
				'Users' => array('label' => __('Users'), 'href' => array('controller' => 'AdminUsers', 'action' => 'index')),
				'Events' => array('label' => __('Events'), 'href' => array('controller' => 'AdminUserLogs', 'action' => 'index')),
				'BkgTasks' => array('label' => __('Bkg.tasks'), 'href' => array('controller' => 'AdminTasks', 'action' => 'index')),
				'Messages' => array('label' => __('Messages'), 'href' => array('controller' => 'AdminMessages', 'action' => 'index')),
			))
		);
		$this->aBottomLinks = $this->aNavBar;
	}
	
	public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->aNavBar = array(
				'Products' => array('label' => __('Products'), 'href' => '', 'submenu' => array(
					'Products' => array('label' => __('Products'), 'href' => array('controller' => 'AdminProducts', 'action' => 'index')),
				)),
			);
			
			if (AuthComponent::user('view_brands')) {
				$this->aNavBar['Products']['submenu']['Brands'] = array('label' => __('Brands'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'Brand'));
			}
			if (AuthComponent::user('load_counters')) {
				$this->aNavBar['Tasks'] = array('label' => __('Uploadings'), 'href' => '', 'submenu' => array());
				$this->aNavBar['Tasks']['submenu'][] = array('label' => __('Upload counters'), 'href' => array('controller' => 'AdminTasks', 'action' => 'task', 'UploadCounters'));
			}
			$this->aNavBar['Orders'] = array('label' => __('Orders'), 'href' => '', 'submenu' => array(
				array('label' => __('Orders'), 'href' => array('controller' => 'AdminOrders', 'action' => 'index')),
				array('label' => __('Agents'), 'href' => array('controller' => 'AdminAgents', 'action' => 'index')),
			));
		}
		
		
	    $this->currMenu = $this->_getCurrMenu();
	    $this->currLink = $this->currMenu;

		if ($id = $this->currUser('id')) {
			$this->loadModel('User');
			$this->User->clear();
			$this->User->save(array('id' => $id, 'last_action' => date('Y-m-d H:i:s')));
		}
	}
	
	public function beforeRender() {
		parent::beforeRender();
		$this->set('isAdmin', $this->isAdmin());

		if ($id = $this->currUser('id')) {
			// get stats about unread messages
			$this->loadModel('Message');
			// $unreadMsgs = $this->Message->findAllByUserIdAndActive($id, true);
			$messages = array(
				'unread' => $this->Message->find('count', array('conditions' => array('user_id' => $id, 'active' => 1))),
				'total' => $this->Message->find('count', array('conditions' => array('user_id' => $id)))
			);

			// set info about recent messages for notify
			$time = $this->Session->read('checkMsgTime');
			$recentMsgs = $this->Message->getRecentMessages($id, $time);
			$this->Session->write('checkMsgTime', date('Y-m-d H:i:s'));
			$this->set(compact('messages', 'recentMsgs'));
		}
	}
	
	public function isAdmin() {
		return AuthComponent::user('id') == 1;
	}

	public function currUser($key = '') {
		if ($key) {
			return AuthComponent::user($key);
		}
		return AuthComponent::user();
	}
	
	public function _getRights($field = 'field') {
		$field_rights = $this->currUser($field.'_rights');
		return ($field_rights) ? explode(',', $field_rights) : array();
	}
	
	public function index() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'AdminProducts'));
			return;
		}

		$aCount = array();
		foreach(array('Brand', 'Category', 'Subcategory', 'Product') as $model) {
			$this->loadModel($model);
			$aCount[$model] = $this->{$model}->find('count');
		}

		$this->loadModel('Task');
		$aTasks = $this->Task->find('all', array(
			'conditions' => array('Task.parent_id' => 0),
			'fields' => array('id', 'created', 'task_name', 'progress', 'total', 'exec_time', 'status', 'xdata'),
			'order' => array('Task.id' => 'desc'),
			'limit' => 10
		));
		$aCached = array();
		$aHangs = array();
		$aRunStatus = array(Task::CREATED, Task::RUN, Task::ABORT);
		foreach($aTasks as &$task) {
			$id = $task['Task']['id'];
			$aCached[$id] = Cache::read($id, 'tasks');
			if ($aCached[$id]) {
				// если есть кэш - получаем инфу о зависании задачи по таймауту
				if (Hash::get($this->Task->getProgressInfo($id), 'hangs')) {
					$aHangs[$id] = true;
				}
			} elseif (in_array($task['Task']['status'], $aRunStatus)) {
				// если задача по статусу выполняется, а кэша нет - это тоже не нормально
				$aHangs[$id] = false;
			}
		}

		$todayTasks = $this->Task->find('count', array(
			'conditions' => array('Task.parent_id' => 0, 'DATE(created)' => date('Y-m-d'))
		));
		$aMainTaskOptions = $this->Task->getOptions(true);

		$this->loadModel('User');
		$aUsersOnline = $this->User->find('all', array(
			'fields' => array('id', 'username', 'last_action'),
			'conditions' => array('User.last_action IS NOT NULL', 'User.last_action > ' => date('Y-m-d H:i:s', time() - MINUTE * 30)),
			'order' => 'User.last_action desc'
		));
		$this->set(compact('aCount', 'aTasks', 'aMainTaskOptions', 'todayTasks', 'aCached', 'aHangs', 'aUsersOnline'));
	}
	
	protected function _getCurrMenu() {
		$curr_menu = strtolower(str_ireplace('Admin', '', $this->request->controller)); // By default curr.menu is the same as controller name
		foreach($this->aNavBar as $currMenu => $item) {
			if (isset($item['submenu'])) {
				foreach($item['submenu'] as $_currMenu => $_item) {
					if (strtolower($_currMenu) === $curr_menu) {
						return $currMenu;
					}
				}
			}
		}
		return $curr_menu;
	}

	public function delete($id) {
		$this->autoRender = false;
		$model = $this->request->query('model');
		if ($model) {
			$this->loadModel($model);
			if (strpos($model, '.') !== false) {
				list($plugin, $model) = explode('.',$model);
			}

			$ids = explode(',', $id);
			$conditions = array($model.'.id' => $ids);
			
			if ($model == 'Brand') {
				$this->_cleanCache('articles_Brand.xml');
			} else if ($model == 'Category') {
				$this->_cleanCache('product_Categories.xml');
				$aCategories = $this->Category->find('all', compact('conditions', 'order'));
				foreach($aCategories as $category) {
					$this->_cleanProductsCache($category);
				}
			}

			$total = $this->{$model}->find('count', compact('conditions'));
			// $this->{$model}->deleteAll($conditions, true, true);
			$this->setFlash(__('%s records have been deleted', $total), 'success');
		}
		if ($backURL = $this->request->query('backURL')) {
			$this->redirect($backURL);
			return;
		}
		$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
	}
	
}
