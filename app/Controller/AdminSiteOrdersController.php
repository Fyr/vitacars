<?php
App::uses('AdminController', 'Controller');
App::uses('SiteOrderCompany', 'Model');
App::uses('SiteOrder', 'Model');
App::uses('SiteOrderDetails', 'Model');
App::uses('Client', 'Model');
App::uses('Product', 'Model');
App::uses('Order', 'Helper/View');
class AdminSiteOrdersController extends AdminController {
    public $name = 'AdminSiteOrders';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('SiteOrderCompany', 'SiteOrder', 'SiteOrderDetails', 'Product');
	public $helpers = array('Order');

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
    	    $completed = $this->request->data('SiteOrder.completed');
    	    if ($this->SiteOrder->save(compact('id', 'completed'))) {
                $baseRoute = array('action' => 'index');
                return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
    	    }
    	} else {
    	    $this->request->data = $this->SiteOrder->findById($id);
    	}
		// $this->PCArticle->setModel('SiteOrder')->edit(&$id, &$lSaved);
		$this->paginate = array(
            'SiteOrderDetails' => array(
                'fields' => array('id', 'Product.title_rus', 'Product.code', 'qty', 'price', 'discount'),
                'conditions' => array('site_order_id' => $id)
            )
        );
        $aProducts = $this->PCTableGrid->paginate('SiteOrderDetails');
        $this->set('aRows', $aProducts);
    }

    public function viewProduct($siteOrderDetail_id) {
        $orderRow = $this->SiteOrderDetails->findById($siteOrderDetail_id);
        $product = $this->Product->findById($orderRow['SiteOrderDetails']['product_id']);
        $code = $product['Product']['code'];
        return $this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.detail_num' => '~'.$code));
    }
}
