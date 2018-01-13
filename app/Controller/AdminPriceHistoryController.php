<?php
App::uses('AdminController', 'Controller');
App::uses('PMFormField', 'Form.Model');
App::uses('FieldTypes', 'Form.Vendor');

class AdminPriceHistoryController extends AdminController
{
    public $name = 'AdminPriceHistory';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('PriceHistory', 'Product', 'Form.PMFormField');

    public function beforeFilter()
    {
        if (!$this->isAdmin()) {
            $this->redirect(array('controller' => 'Admin', 'action' => 'index'));
            return;
        }
        parent::beforeFilter();
    }

    public function index()
    {
        $this->paginate = array(
            'fields' => array('created', 'product_id', 'fk_id', 'old_price', 'new_price')
        );
        $data = $this->PCTableGrid->paginate('PriceHistory');

        $fields = array('id', 'label');
        $conditions = array('field_type' => FieldTypes::PRICE);
        $aFormFields = $this->PMFormField->find('list', compact('fields', 'conditions'));
        $aProducts = $this->Product->findAllById(array_unique(Hash::extract($data, '{n}.PriceHistory.product_id')));
        $this->set(compact('data', 'aFormFields', 'aProducts'));
    }

}
