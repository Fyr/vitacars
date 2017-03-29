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
			)),
			// 'slider' => array('label' => __('Slider'), 'href' => array('controller' => 'AdminSlider', 'action' => 'index')),
			// 'settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index'))
			'System' => array('label' => __('System'), 'href' => '', 'submenu' => array(
				'Settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index')),
				'Users' => array('label' => __('Users'), 'href' => array('controller' => 'AdminUsers', 'action' => 'index')),
				'Events' => array('label' => __('Events'), 'href' => array('controller' => 'AdminUserLogs', 'action' => 'index')),
				'BkgTasks' => array('label' => __('Bkg.tasks'), 'href' => array('controller' => 'AdminTasks', 'action' => 'index')),
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
				$this->aNavBar['Upload'] = array('label' => __('Uploadings'), 'href' => '', 'submenu' => array());
				$this->aNavBar['Upload']['submenu'][] = array('label' => __('Upload counters'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'index'));
			}
			$this->aNavBar['Orders'] = array('label' => __('Orders'), 'href' => '', 'submenu' => array(
				array('label' => __('Orders'), 'href' => array('controller' => 'AdminOrders', 'action' => 'index')),
				array('label' => __('Agents'), 'href' => array('controller' => 'AdminAgents', 'action' => 'index')),
			));
		}
		
		
	    $this->currMenu = $this->_getCurrMenu();
	    $this->currLink = $this->currMenu;
	}
	
	public function beforeRender() {
		parent::beforeRender();
		$this->set('isAdmin', $this->isAdmin());
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
		$this->redirect(array('controller' => 'AdminProducts'));
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
			$this->{$model}->deleteAll(array($model.'.id' => $ids), true, true);
		}
		if ($backURL = $this->request->query('backURL')) {
			$this->redirect($backURL);
			return;
		}
		$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
	}
	
}
