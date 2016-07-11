<?php
App::uses('AdminController', 'Controller');
App::uses('Order', 'AppModel');
App::uses('OrderProduct', 'AppModel');
App::uses('DetailNum', 'AppModel');
App::uses('Category', 'AppModel');
App::uses('Media', 'Media.Model');
App::uses('PMFormField', 'Form.Model');
App::uses('PMFormData', 'Form.Model');
App::uses('FieldTypes', 'Form.Vendor');
App::uses('CsvReader', 'Vendor');
App::uses('FieldTypes', 'Form.Vendor');
App::uses('Price', 'View/Helper');
class AdminOrdersController extends AdminController {
    public $name = 'AdminOrders';
    public $uses = array('Order', 'OrderProduct', 'DetailNum', 'Category', 'Media.Media', 'Form.PMFormField', 'Form.PMFormData');
	public $helpers = array('Price');

	/*
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
	*/
	public function index() {
		/*
		$this->paginate = array(
			'fields' => array('Product.id', 'Product.title_rus', 'Product.detail_num', 'Product.code', 'Product.cat_id', 'Product.brand_id', 'OrderProduct.qty'),
		);
		*/
		$this->paginate = array();
		if (!$this->isAdmin()) {
			$this->paginate['conditions'] = array('user_id' => $this->currUser('id'));
		} else {

		}
		$aRowset = $this->PCTableGrid->paginate('Order');
	}

    public function edit($order_id) {
		$order = $this->Order->findById($order_id);
		$user_id = Hash::get($order, 'Order.user_id');
		if (!$order || ($this->currUser('id') != $user_id && !$this->isAdmin())) {
			$this->redirect(array('action' => 'index'));
			return;
		}
		$this->set(compact('order'));

		// Process available prices
		if ($this->isAdmin() && $this->currUser('id') != $user_id) {
			$this->loadModel('User');
			$fieldRights = Hash::get($this->User->findById($user_id), 'User.field_rights');
			$fieldRights = ($fieldRights) ? explode(',', $fieldRights) : array();
		} else {
			$fieldRights = $this->_getRights();
		}
		$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		$aFields = array();
		$aColumns = array();
		foreach($aParams as $fk_id => $param) {
			if (in_array($fk_id, $fieldRights)) {
				$field = 'PMFormData.fk_' . $fk_id;

				switch ($aParams[$fk_id]['PMFormField']['field_type']) {
					case FieldTypes::INT:
					case FieldTypes::FLOAT:
					case FieldTypes::FORMULA:
						$format = 'integer';
						break;
					default:
						$format = 'string';
				}

				$aFields[] = $field;
				$aColumns[$field] = array(
					'id' => $fk_id,
					'key' => $field,
					'label' => $aParams[$fk_id]['PMFormField']['label'],
					'format' => $format,
					'is_price' => $aParams[$fk_id]['PMFormField']['is_price'] && true,
					'field_type' => $aParams[$fk_id]['PMFormField']['field_type']
				);

				if ($aParams[$fk_id]['PMFormField']['is_price']) {
					$aColumns[$field . '_discount'] = array(
						'id' => $fk_id,
						'key' => $field . '_discount',
						'label' => __('Discount'),
						'format' => 'integer',
						'is_price' => false,
					);
					$aColumns[$field . '_sum'] = array(
						'id' => $fk_id,
						'key' => $field . '_sum',
						'label' => __('Sum'),
						'format' => 'integer',
						'is_price' => false
					);
				}
			}
		}
		$this->set(compact('aColumns'));

		$this->paginate = array(
			'fields' => array('Product.id', 'Product.title_rus', 'Product.detail_num', 'Product.code', 'Product.cat_id', 'Product.brand_id', 'OrderProduct.number', 'OrderProduct.qty'),
			'conditions' => compact('order_id'),
			'order' => array('number' => 'ASC', 'id' => 'ASC')
		);
		$aRowset = $this->PCTableGrid->paginate('OrderProduct');
		$this->set(compact('aRowset'));

		$product_ids = Hash::extract($aRowset, '{n}.Product.id');
		$fields = am($aFields, array('object_id'));
		$conditions = array('object_id' => $product_ids);
		$aFormData = $this->PMFormData->find('all', compact('fields', 'conditions'));
		$aFormData = Hash::combine($aFormData, '{n}.PMFormData.object_id', '{n}');
		$this->set('aFormData', $aFormData);

		$aProductMedia = $this->Media->getList(array('media_type' => 'image', 'object_type' => 'Product', 'object_id' => $product_ids, 'main_by' => 1));
		$aProductMedia = Hash::combine($aProductMedia, '{n}.Media.object_id', '{n}');
		$this->set('aProductMedia', $aProductMedia);

		$aCategories = $this->Category->findAllById(Hash::extract($aRowset, '{n}.Product.cat_id'));
		$aCategories = Hash::combine($aCategories, '{n}.Category.id', '{n}.Category');
		$this->set('aCategories', $aCategories);

		$brand_ids = Hash::extract($aRowset, '{n}.Product.brand_id');
		$brand_ids = array_unique($brand_ids);

		$aBrandMedia = $this->Media->getList(array('media_type' => 'image', 'object_type' => 'Brand', 'object_id' => $brand_ids, 'main_by' => 1));
		$aBrandMedia = Hash::combine($aBrandMedia, '{n}.Media.object_id', '{n}');
		$this->set('aBrandMedia', $aBrandMedia);
    }
    
    public function upload() {
		if ($file = Hash::get($_FILES, 'csv_file.tmp_name')) {
			try {
				$this->Order->trxBegin();
				$aData = CsvReader::parse($file);
				$this->Order->save(array('user_id' => $this->currUser('id')));
				$aProducts = $this->_processUpload($this->Order->id, $aData['data']);
				$this->Order->save(array('items' => count($aProducts)));
				$this->Order->trxCommit();

				$this->setFlash(__('%s lines processed, %s products added', count($aData['data']), count($aProducts)), 'success');
				$this->redirect(array('action' => 'edit', $this->Order->id));
			} catch (Exception $e) {
				$this->Order->trxRollback();
				$this->setFlash($e->getMessage(), 'error');
			}
		}
	}

	private function _processUpload($order_id, $aData, $keyField = 'detail_num') {

		$aProducts = array();
		// TODO: проверить номера на уникальность, если не уникальные - суммировать

		foreach($aData as $i => $row) {
			list($number, $qty) = array_values($row);
			if ($keyField == 'detail_num') {
				$conditions = array('detail_num' => $this->DetailNum->strip($number), 'num_type' => DetailNum::ORIG);
				$ids = $this->DetailNum->find('all', compact('conditions'));
				$ids = Hash::extract($ids, '{n}.DetailNum.product_id');
				$ids = array_unique($ids);
			} else {
				$fields = array('Product.id');
				$conditions = array('Product.code' => $number);
				$ids = $this->Product->find('all', compact('fields', 'conditions'));
				$ids = Hash::extract($ids, '{n}.Product.id');
			}

			foreach($ids as $product_id) {
				$aProducts[] = $product_id;
				$this->OrderProduct->clear();
				$this->OrderProduct->save(compact('order_id', 'number', 'product_id', 'qty'));
			}
		}
		return $aProducts;
	}
}
