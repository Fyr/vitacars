<?php
App::uses('AdminController', 'Controller');
App::uses('Order', 'AppModel');
App::uses('OrderProduct', 'AppModel');
App::uses('Product', 'AppModel');
App::uses('DetailNum', 'AppModel');
App::uses('Category', 'AppModel');
App::uses('Media', 'Media.Model');
App::uses('PMFormField', 'Form.Model');
App::uses('PMFormData', 'Form.Model');
App::uses('Brand', 'AppModel');
App::uses('FieldTypes', 'Form.Vendor');
App::uses('CsvReader', 'Vendor');
App::uses('FieldTypes', 'Form.Vendor');
App::uses('Price', 'View/Helper');
class AdminOrdersController extends AdminController {
    public $name = 'AdminOrders';
	public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Order', 'OrderProduct', 'Product', 'DetailNum', 'Category', 'Subcategory', 'Media.Media', 'Form.PMFormField', 'Form.PMFormData', 'Brand', 'Agent');
	public $helpers = array('Price', 'Tpl');

	private function isOrderAllowed($user_id) {
		return $this->isAdmin() || $this->currUser('orders') || $user_id == $this->currUser('id');
	}

	public function index() {
		$this->paginate = array(
			'fields' => array('created', 'agent_id', 'agent2_id', 'items', 'sum', 'nds', 'paid', 'currency')
		);
		if (!($this->isAdmin() || $this->currUser('orders'))) {
			$this->paginate['conditions'] = array('user_id' => $this->currUser('id'));
		} else {

		}
		$aRowset = $this->PCTableGrid->paginate('Order');

		$aAgentOptions = $this->Agent->getOptions();
		$this->set(compact('aRowset', 'aAgentOptions'));
	}

    public function details($order_id) {
		$order = $this->Order->findById($order_id);
		$user_id = Hash::get($order, 'Order.user_id');
		if (!$order || !$this->isOrderAllowed($user_id)) {
			$this->redirect(array('action' => 'index'));
			return;
		}
		$this->set(compact('order'));

		if ($this->request->is(array('post', 'put'))) {
			$xdata = $this->request->data('xdata');
			$json_data = json_decode($xdata, true);
			$aRowset = $this->OrderProduct->findAllById(array_keys($json_data));
			$_total = 0;
			foreach ($aRowset as &$Product) {
				$id = $Product['OrderProduct']['id'];
				$discount = intval($json_data[$id]['discount']);
				$price = floatval($json_data[$id]['price']);
				$qty = intval($json_data[$id]['qty']);
				$sum = $price * $qty;
				$sum = round($sum - $sum * $discount / 100, 2);
				$_total += $sum;
			}
			$this->request->data('Order', array(
				'id' => $order_id,
				'items' => count($aRowset),
				'xdata' => $xdata,
				'brand_ids' => $this->request->data('brand_ids'),
				'sum' => $_total
			));
			$this->Order->save($this->request->data('Order'));
			$this->setFlash(__('Order state is saved'), 'success');
			$this->redirect(array('action' => 'details', $order_id));
			return;
		}
		// Загружаем права доступа на поля для ордера
		$fieldRights = $this->_getRights('price');
		if (($this->isAdmin() || $this->currUser('orders')) && $this->currUser('id') != $user_id) { // если админ просматривает чужой
			$this->loadModel('User');
			$fieldRights = Hash::get($this->User->findById($user_id), 'User.price_rights');
			$fieldRights = ($fieldRights) ? explode(',', $fieldRights) : array();
		}
		$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		$aFields = array();
		$aColumns = array();
		$aStockKeys = array('A1', 'A2', 'A3', 'A4', 'A5', 'A6');
		$this->set('aStockKeys', $aStockKeys);
		foreach($aParams as $fk_id => $param) {
			if (in_array($aParams[$fk_id]['PMFormField']['key'], $aStockKeys)) {
				Configure::write('Params.'.$aParams[$fk_id]['PMFormField']['key'], $fk_id);
			}
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

				$aFields[] = $field; // Поля для получения данных из PMFormData
				$aColumns[$field] = array( // колонки для TableGrid
					'id' => $fk_id,
					'key' => $field,
					'label' => $aParams[$fk_id]['PMFormField']['label'],
					'format' => $format,
					'is_price' => $aParams[$fk_id]['PMFormField']['is_price'] && true,
					'field_type' => $aParams[$fk_id]['PMFormField']['field_type']
				);
			}
		}
		$aColumns['discount'] = array(
			'id' => 'discount',
			'key' => 'discount',
			'label' => __('Discount'),
			'format' => 'integer',
			'is_price' => false,
		);
		$aColumns['row_sum'] = array(
			'id' => 'row_sum',
			'key' => 'row_sum',
			'label' => __('Sum'),
			'format' => 'integer',
			'is_price' => false
		);

		$this->set(compact('aColumns'));

		$this->paginate = array(
			'fields' => array(
				'Product.id', 'Product.title_rus', 'Product.detail_num', 'Product.code', 'Product.cat_id', 'Product.brand_id',
				'OrderProduct.nn', 'OrderProduct.number', 'OrderProduct.qty'
			),
			'conditions' => compact('order_id'),
			'order' => array('OrderProduct.id' => 'ASC')
		);

		$filterBrand = Hash::get($order, 'Order.brand_ids');
		$filterBrand = ($filterBrand) ? explode(',', $filterBrand) : array();
		if (isset($this->request->named['Product.brand_id']) && $this->request->named['Product.brand_id']) {
			$brand_ids = $this->request->named['Product.brand_id'];
			$filterBrand = ($brand_ids && $brand_ids !== 'null') ? explode(',', $brand_ids) : array();
			unset($this->request->params['named']['Product.brand_id']);
		}
		if ($filterBrand) {
			$this->paginate['conditions']['Product.brand_id'] = $filterBrand;
		}
		$this->set(compact('filterBrand'));

		$aRowset = $this->PCTableGrid->paginate('OrderProduct');
		$this->set(compact('aRowset'));

		$product_ids = Hash::extract($aRowset, '{n}.Product.id');
		$aFields[] = 'object_id';
		foreach($aStockKeys as $key) {
			$aFields[] = 'PMFormData.fk_'.Configure::read('Params.'.$key);
		}
		$fields = array_unique($aFields);
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

		$conditions = array('order_id' => $order_id);
		$group = 'Product.brand_id';
		$fields = array('Product.brand_id');
		$brands = $this->OrderProduct->find('all', compact('fields', 'conditions', 'group'));
		$brand_ids = Hash::extract($brands, '{n}.Product.brand_id');

		$aBrandOptions = $this->Brand->find('list', array('conditions' => array('id' => $brand_ids, 'published' => 1)));
		$this->set('aBrandOptions', $aBrandOptions);

		$aBrandMedia = $this->Media->getList(array('media_type' => 'image', 'object_type' => 'Brand', 'object_id' => $brand_ids, 'main_by' => 1));
		$aBrandMedia = Hash::combine($aBrandMedia, '{n}.Media.object_id', '{n}');
		$this->set('aBrandMedia', $aBrandMedia);
    }

	/*
    public function upload() {
		if ($file = Hash::get($_FILES, 'csv_file.tmp_name')) {
			try {
				$this->Order->trxBegin();
				$aData = CsvReader::parse($file, array('detail_num', 'qty'));
				$this->Order->save(array('user_id' => $this->currUser('id')));
				$aProducts = $this->_processUpload($this->Order->id, $aData['data'], 'code');
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
	*/

	private function _processUpload($order_id, $aData, $keyField = 'detail_num') {

		$aProducts = array();
		// TODO: проверить номера на уникальность, если не уникальные - суммировать

		foreach($aData as $i => $row) {
			$nn = $i + 1;
			list($number, $qty) = array_values($row);
			if ($keyField == 'detail_num') {
				$number = $this->DetailNum->strip($number);
				$ids = $this->DetailNum->findDetails($this->DetailNum->stripList('*'.$number.'*'), true);
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
				$this->OrderProduct->save(compact('order_id', 'nn', 'number', 'product_id', 'qty'));
			}
		}
		return $aProducts;
	}

	public function printXls($order_id) {
		$order = $this->Order->findById($order_id);
		$user_id = Hash::get($order, 'Order.user_id');
		if (!$this->isOrderAllowed($user_id)) {
			$this->redirect(array('action' => 'index'));
			return;
		}
		if ($this->request->is(array('put', 'post'))) {
			ignore_user_abort(true);
			set_time_limit(0);

			$this->layout = 'print_xls';

			$json_data = json_decode($this->request->data('json_data'), true);
			$aRowset = $this->OrderProduct->findAllById(array_keys($json_data));
			$printCols = $this->request->data('printCols');
			$printCols = ($printCols) ? explode(',', $printCols) : array();

			$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
			$aFieldOptions = array(
				'Category.title' => __('Brand'),
				'Product.title_rus' => __('Title rus'),
				'Product.code' => __('Code'),
			);
			foreach($aParams as $fk_id => $param) {
				$key = 'PMFormData.fk_'.$fk_id;
				$aFieldOptions[$key] = $param['PMFormField']['label'];
			}
			$aFieldOptions = Hash::merge($aFieldOptions, array(
				'OrderProduct.qty' => __('Qty'),
				'price' => __('Price'),
				'discount' => __('Discount'),
				'row_sum' => __('Sum')
			));
			foreach($aFieldOptions as $key => $label) {
				if (!in_array($key, $printCols)) {
					unset($aFieldOptions[$key]);
				}
			}

			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.cat_id'));
			$conditions = array('Category.object_type' => 'Category', 'Category.id' => $ids);
			$aCategories = $this->Category->find('list', compact('conditions'));

			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.subcat_id'));
			$conditions = array('Subcategory.object_type' => 'Subcategory', 'Subcategory.id' => $ids);
			$aSubcategories = $this->Subcategory->find('list', compact('conditions'));

			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.brand_id'));
			$conditions = array('Brand.object_type' => 'Brand', 'Brand.id' => $ids);
			$aBrands = $this->Brand->find('list', compact('conditions'));

			$order['Order']['created'] = date('d.m.Y', strtotime($order['Order']['created']));
			$agent = $this->Agent->findById($order['Order']['agent_id']);
			$agent2 = $this->Agent->findById($order['Order']['agent2_id']);

			$tpl_data = array();
			$tpl_data['Order'] = $order['Order'];
			$tpl_data['Agent'] = $agent['Agent'];
			$tpl_data['Agent2'] = $agent2['Agent'];

			$ids = array_unique(Hash::extract($aRowset, '{n}.Product.id'));
			$aFormData = Hash::combine($this->PMFormData->findAllByObjectId($ids), '{n}.PMFormData.object_id', '{n}.PMFormData');
			$_total = 0;
			foreach ($aRowset as &$Product) {
				$id = $Product['OrderProduct']['id'];
				$Product['discount'] = $discount = intval($json_data[$id]['discount']);
				$Product['price'] = $price = floatval($json_data[$id]['price']);
				$Product['qty'] = $qty = intval($json_data[$id]['qty']);
				$sum = $price * $qty;
				$Product['sum'] = $sum = round($sum - $sum * $discount / 100, 2);
				$_total += $sum;

				$Product['PMFormData'] = $aFormData[$Product['Product']['id']];
			}
			$nds = round($_total * $order['Order']['nds'] / 100, 2);
			$tpl_data['Itogo'] = array(
				'items' => count($aRowset),
				'sum' => $_total,
				'nds' => $nds,
				'k_oplate' => $_total + $nds,
				'k_oplate_propis' => 'сумма прописью'
			);

			$filename = 'Счет-фактура_N_'.$order_id.'_от_'.date('Y-m-d', strtotime($order['Order']['created'])).'_'.str_replace(' ', '_', $tpl_data['Agent2']['title']).'.xls';
			$this->set(compact('aRowset', 'aCategories', 'aSubcategories', 'aBrands', 'tpl_data', 'filename', 'aFieldOptions', 'printCols', 'aParams'));

			$this->set('sf_header', Configure::read('Settings.sf_header'));
			$this->set('sf_footer', Configure::read('Settings.sf_footer'));
		} else {

		}
	}

	public function edit($id = 0) {
		$lAllowed = true;
		if ($id) {
			$order = $this->Order->findById($id);
			$user_id = Hash::get($order, 'Order.user_id');
			if (!$this->isOrderAllowed($user_id)) {
				$this->redirect(array('action' => 'index'));
				return;
			}
		}

		if (!$id) {
			$this->request->data('Order.user_id', $this->currUser('id'));
		}
		if ($this->request->is(array('put', 'post'))) {
			if ($id) { //
				$this->request->data('Order.id', $id);
				$this->Order->save($this->request->data);
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			} else { // создать счет и загрузить детальные строки
				try {
					$this->Order->trxBegin();

					$aData = array();
					if ($file = Hash::get($_FILES, 'csv_file.tmp_name')) {
						$aData = CsvReader::parse($file, array('detail_num', 'qty'));
						$aData = Hash::get($aData, 'data');
					}
					if ($details_text = trim($this->request->data('Order.details_text'))) {
						foreach(explode("\n", str_replace(array("\r\n"), "\n", $details_text)) as $line => $row) {
							if (!strpos($row, ';')) {
								throw new Exception(__('Incorrect format line %s in text', $line + 1));
							}
							list($detail_num, $qty) = explode(';', trim($row));
							if (!($detail_num && $qty)) {
								throw new Exception(__('Incorrect format line %s in text', $line + 1));
							}
							$aData[] = compact('detail_num', 'qty');
						}
					}

					$this->Order->save($this->request->data);
					$aProducts = $this->_processUpload($this->Order->id, $aData, 'detail_num');
					$this->Order->set('items', count($aProducts));
					$this->Order->save();

					$this->Order->trxCommit();

					$this->setFlash(__('%s lines processed, %s products added', count($aData), count($aProducts)), 'success');
					$this->redirect(array('action' => 'details', $this->Order->id));
				} catch (Exception $e) {
					$this->Order->trxRollback();
					$this->setFlash($e->getMessage(), 'error');
				}
			}
		} else {
			$this->request->data = $this->Order->findById($id);
			if (!$id) {
				$this->request->data('Order.nds', '0');
				$this->request->data('Order.currency', 'rur');
			}
		}

		$aAgentOptions = $this->Agent->getOptions(1);
		$aAgent2Options = $this->Agent->getOptions(2);
		$aCurrencyOptions = array(
			'byn' => 'BYN (бел.деномин.рубль)',
			'rur' => 'RUR (росс.рубль)',
			'usd' => 'USD (доллар США)',
			'eur' => 'EUR (Евро)'
		);
		$this->set(compact('aAgentOptions', 'aAgent2Options', 'aCurrencyOptions'));
	}

	public function addDetail($order_id) {
		$order = $this->Order->findById($order_id);
		$user_id = Hash::get($order, 'Order.user_id');
		if (!$this->isOrderAllowed($user_id)) {
			$this->redirect(array('action' => 'index'));
			return;
		}

		$keyField = 'detail_num';
		$qty = 0;
		if ($number = $this->request->data('detail_num')) {
			if ($keyField == 'detail_num') {
				$number = $this->DetailNum->strip($number);
				$ids = $this->DetailNum->findDetails($this->DetailNum->stripList('*'.$number.'*'), true);
				$ids = array_unique($ids);
			} else {
				$fields = array('Product.id');
				$conditions = array('Product.code' => $number);
				$ids = $this->Product->find('all', compact('fields', 'conditions'));
				$ids = Hash::extract($ids, '{n}.Product.id');
			}
			try {
				$aProducts = array();
				$this->OrderProduct->trxBegin();
				foreach ($ids as $product_id) {
					$aProducts[] = $product_id;
					$this->OrderProduct->clear();
					$this->OrderProduct->save(compact('order_id', 'number', 'product_id', 'qty'));
				}
				$this->OrderProduct->trxCommit();
				$this->setFlash(__('%s products have been added', count($aProducts)), 'success');
			} catch (Exception $e) {
				$this->OrderProduct->trxRollback();
				$this->setFlash($e->getMessage(), 'error');
			}
		}
		$this->redirect(array('action' => 'details', $order_id));
	}

	/*
	public function delete($id) {
		$model = $this->request->query('model');
		if ($model === 'OrderProduct') {
			$orderDetail = $this->OrderProduct->findById($id);
			if ($orderDetail) {
				$model = 'Order';
				$id = Hash::get($orderDetail, 'OrderProduct.order_id');
				$order = $this->Order->findById($id);
				$user_id = Hash::get($order, 'Order.user_id');
				if ($this->isOrderAllowed($user_id)) {
					parent::delete($id);
					$this->setFlash(__('Products have been added', count($aProducts)), 'success');
					return;
				}
			}
		} elseif ($model === 'Order') {
			$order = $this->Order->findById($id);
			$user_id = Hash::get($order, 'Order.user_id');
			$isAllowed = $this->isOrderAllowed($user_id);
			if ($this->isOrderAllowed($user_id)) {
				parent::delete($id);
				return;
			}
		}
		$this->redirect(array('action' => 'index'));
	}
	*/
}
