<?php
App::uses('AdminController', 'Controller');
App::uses('SiteOrder', 'Model');
App::uses('Client', 'Model');
class AdminSiteOrdersController extends AdminController {
    public $name = 'AdminSiteOrders';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('SiteOrder');

    public function beforeRender() {
		$this->currMenu = 'Clients';
    	parent::beforeRender();
    	$this->set('objectType', 'SiteOrder');
    }

    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'created', 'modified', 'zone', 'Client.*', 'SiteOrderCompany.*', 'username', 'email', 'phone', 'comment', 'address', 'completed'),
    		// 'conditions' => array('SiteOrder.group_id <> ' => SiteOrder::GROUP_ADMIN),
    		'order' => array('SiteOrder.created' => 'DESC')
    	);
    	$aRows = $this->PCTableGrid->paginate('SiteOrder');
    	$this->set('aRows', $aRows);
    }

    public function edit($id = 0) {
        if ($this->request->is(array('post', 'put'))) {
            $this->request->data('SiteOrder.username', $this->request->data('SiteOrder.email'));
        }
    	$this->PCArticle->setModel('SiteOrder')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$id = $this->SiteOrder->id;
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
    }
}
