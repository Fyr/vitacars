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
			'Users' => array('label' => __('Users'), 'href' => array('controller' => 'AdminUsers', 'action' => 'index')),
			// 'slider' => array('label' => __('Slider'), 'href' => array('controller' => 'AdminSlider', 'action' => 'index')),
			// 'settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index'))
			'Upload' => array('label' => __('Uploadings'), 'href' => '', 'submenu' => array(
				array('label' => __('Upload counters'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'index')),
				array('label' => __('Upload new products'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'uploadNewProducts')),
				array('label' => __('Check products'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'checkProducts')),
			)),
			'Reports' => array('label' => __('Reports'), 'href' => '', 'submenu' => array(
				array('label' => __('Sales by period'), 'href' => array('controller' => 'AdminReports', 'action' => 'sales')),
			)),
			'System' => array('label' => __('System'), 'href' => '', 'submenu' => array(
				'Export' => array('label' => __('Data export'), 'href' => array('controller' => 'AdminExport', 'action' => 'index')),
				'Settings' => array('label' => __('Settings'), 'href' => array('controller' => 'AdminSettings', 'action' => 'index')),
				'Events' => array('label' => __('Events'), 'href' => array('controller' => 'AdminUserLogs', 'action' => 'index')),
			))
		);
		$this->aBottomLinks = $this->aNavBar;
	}
	
	public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->aNavBar = array(
				'Products' => array('label' => __('Products'), 'href' => '', 'submenu' => array(
					'Products' => array('label' => __('Products'), 'href' => array('controller' => 'AdminProducts', 'action' => 'index')),
				))
			);
			
			if (AuthComponent::user('view_brands')) {
				$this->aNavBar['Products']['submenu']['Brands'] = array('label' => __('Brands'), 'href' => array('controller' => 'AdminContent', 'action' => 'index', 'Brand'));
				fdebug($this->aNavBar);
			}
			if (AuthComponent::user('load_counters')) {
				$this->aNavBar['Upload'] = array('label' => __('Uploadings'), 'href' => '', 'submenu' => array(
					array('label' => __('Upload counters'), 'href' => array('controller' => 'AdminUploadCsv', 'action' => 'index')),
				));
			}
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
	
	protected function _getFieldRights() {
		$field_rights = AuthComponent::user('field_rights');
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
			$this->{$model}->deleteAll(array($model.'.id' => $ids));
		}
		if ($backURL = $this->request->query('backURL')) {
			$this->redirect($backURL);
			return;
		}
		$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
	}
	
}
