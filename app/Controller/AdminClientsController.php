<?php
App::uses('AdminController', 'Controller');
App::uses('Client', 'Model');
App::uses('Brand', 'Model');
App::uses('ClientBrandDiscount', 'Model');
class AdminClientsController extends AdminController {
    public $name = 'AdminClients';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('Client', 'Brand', 'ClientBrandDiscount');

    public function beforeRender() {
		$this->currMenu = 'Clients';
    	parent::beforeRender();
    	$this->set('objectType', 'Client');
    }

    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'created', 'modified', 'zone', 'group_id', 'ClientCompany.*', 'fio', 'email', 'phone', 'active'),
    		'conditions' => array('Client.group_id <> ' => Client::GROUP_ADMIN),
    		'order' => array('Client.created' => 'DESC')
    	);
    	$aRows = $this->PCTableGrid->paginate('Client');
    	$this->set('aRows', $aRows);
    }

    public function edit($id = 0) {
        if ($this->request->is(array('post', 'put'))) {
            $this->request->data('Client.username', $this->request->data('Client.email'));
        }
    	$this->PCArticle->setModel('Client')->edit(&$id, &$lSaved);
		if ($lSaved) {
		    $this->ClientBrandDiscount->deleteAll(array('client_id' => $id));
		    $data = array();
            foreach($this->request->data('ClientBrandDiscount') as $brand => $discount) {
                $brand_id = str_replace('brand_', '', $brand);
                if ($discount) {
                    $data[] = array('client_id' => $id, 'brand_id' => $brand_id, 'discount' => $discount);
                }
            }
            $this->ClientBrandDiscount->saveMany($data);
			$id = $this->Client->id;
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}

		$this->paginate = array(
            'Brand' => array(
                'fields' => array('Brand.title'),
                'conditions' => array('is_fake' => 0)
            )
        );
        $aRows = $this->PCTableGrid->paginate('Brand');

        $aDiscounts = $this->ClientBrandDiscount->find('list', array(
            'fields' => array('brand_id', 'discount'),
            'conditions' => array('client_id' => $id)
        ));

        $this->set(compact('aRows', 'aDiscounts'));
    }
}
