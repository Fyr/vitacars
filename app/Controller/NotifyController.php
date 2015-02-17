<?php
App::uses('AdminController', 'Controller');
App::uses('CakeEmail', 'Network/Email');
class NotifyController extends AppController {
	public $name = 'Notify';
	public $uses = array('Settings', 'Product', 'Form.PMFormField');
		
	public function index() {
		$this->autoRender = false;
		$fieldColor = 'PMFormData.fk_'.Configure::read('Params.color');
		$fields = array('Product.title', 'Product.code', 'PMFormData.fk_'.Configure::read('Params.A1'), 'PMFormData.fk_'.Configure::read('Params.A2'));
		$conditions = array($fieldColor => array(1, 2));
		$order = array($fieldColor => 'DESC');
		$aRowset = $this->Product->find('all', compact('conditions', 'order'));
		
		$mails = explode('<br />', str_replace(array("\r\n", "\r", "n"), '', nl2br(Configure::read('Settings.manager_emails'))));
		$this->set(compact('aRowset'));
		
		$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		$this->set('aParams', $aParams);
    	
    	// return $this->render('notify_lowremains', 'ajax');
		$Email = new CakeEmail();
		$Email->template('notify_lowremains')->viewVars(compact('aRowset', 'aParams'))
			->emailFormat('html')
			->from('info@'.DOMAIN_NAME)
			->to($mails)
			->bcc(Configure::read('Settings.admin_email'))
			->subject(DOMAIN_TITLE.': Критические остатки на складе')
			->send();
	}
}
