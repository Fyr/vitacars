<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('FieldTypes', 'Form.Vendor');
class AdminProductsController extends AdminController {

    public $name = 'AdminProducts';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('Product', 'Form.PMForm', 'Form.PMFormField', 'Form.PMFormData', 'Form.PMFormConst', 'Form.PHFormField', 'User', 'Category', 'Subcategory', 'Brand', 'ProductRemain', 'Media.Media', 'Search', 'DetailNum', 'FormPrice');
    public $helpers = array('ObjectType', 'Form.PHFormFields', 'Form.PHFormData', 'Price');

    private $paramDetail, $aFormula, $aFieldKeys, $aBrandOptions, $aFields, $searchDetail, $skladOstatki;

	public function beforeFilter() {
		parent::beforeFilter();
		$conditions = array('Brand.object_type' => 'Brand');
		if (!Configure::read('Settings.show_fake')) {
			$conditions['Brand.is_fake'] = 0;
		}
		$order = 'Brand.title';
		$this->aBrandOptions = $this->Brand->find('list', compact('conditions', 'order'));
	}

    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', $this->Product->objectType);
    }

	private function _getBrandOptions($brands_ids = array()) {
		if (!$brands_ids) {
			$brands_ids = $this->_getBrandRights();
		} else {
			$brands_ids = array_intersect($this->_getBrandRights(), $brands_ids);
		}
		$options = array();
		foreach($this->aBrandOptions as $id => $title) {
			if (in_array($id, $brands_ids)) {
				$options[$id] = $title;
			}
		}
		return $options;
	}

	private function _getBrandRights() {
		$field_rights = AuthComponent::user('brand_rights');
		// если нет прав доступа на брэнды - возвращаем все
		return ($field_rights) ? explode(',', $field_rights) : array_keys($this->aBrandOptions);
	}

    private function _processParams() {
        $field_rights = $this->_getRights();
    	$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
    	$this->set('aParams', $aParams);
    	$aLabels = array();
    	$aFields = array();
		$aCols = array();
    	$paramMotor = 0;
		$this->skladOstatki = array();
    	foreach($aParams as $id => $_field) {
	    	if (!$field_rights || in_array($_field['PMFormField']['id'], $field_rights)) {
	    		$alias = 'PMFormData.fk_'.$id;
				$aFields[] = $alias;
				$aLabels[$alias] = $_field['PMFormField']['label'];

				if ($_field['PMFormField']['id'] == Product::MOTOR) {
					$this->set('paramMotor', 'fk_'.$id);
				}
				$aCols[$alias] = array(
					'key' => $alias,
					'label' => $_field['PMFormField']['label'],
					'format' => (in_array($_field['PMFormField']['field_type'], array(FieldTypes::INT, FieldTypes::FLOAT, FieldTypes::FORMULA, FieldTypes::PRICE))) ? 'integer' : 'string'
				);
				if ($_field['PMFormField']['is_stock']) {
					$this->skladOstatki[] = $alias;
				}
    		}
    	}
    	$this->set('aLabels', $aLabels);
		$discountComment = 'PMFormData.fk_'.Configure::read('Params.discountComment');
		if (!in_array($discountComment, $aFields)) {
			$aFields[] = $discountComment;
		}
		if (!$this->_isGridFilter()) {
			$this->aFields = $aFields;
			$aFields = array();
			$this->set('aCols', $aCols);
		}
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'title_rus', 'detail_num', 'code', 'brand_id', 'cat_id'), $aFields)
        );
		if (Configure::read('Settings.show_fake')) {
			array_unshift($this->paginate['fields'], 'is_fake');
		}

        $detail_num = '';
        if (isset($this->request->named['Product.detail_num']) && ($detail_num = $this->request->named['Product.detail_num'])) {
        	if ((strpos($detail_num, '*') !== false) || (strpos($detail_num, '~') !== false)) {
        		$detail_num = str_replace(array('*', '~'), '', $detail_num);
        		$this->set('detail_num', $detail_num);
        		if ($detail_num) {
					$this->processFilter($detail_num);
        		}
			}
            unset($this->request->params['named']['Product.detail_num']);
        }

        if (isset($this->request->named['PMFormData.fk_6']) && $motor = $this->request->named['PMFormData.fk_6']) {
        	$motor = explode(' ', str_replace('*', '', $motor));
        	$ors = array();
        	foreach($motor as $_motor) {
        		$ors[] = 'PMFormData.fk_6 LIKE "%'.$_motor.'%"';
        	}
        	$this->paginate['conditions'][] = array('OR' => $ors);
        	$this->set('motorFilterValue', $motor);
        	unset($this->request->params['named']['PMFormData.fk_6']);
        }

        if (!$this->isAdmin()) {
        	if (!$detail_num) {
        		// запретить не-админам показывать полный список
        		$this->paginate['conditions'] = array('0=1');
        	}
        }

		$brand_ids = $this->_getBrandRights();
		if (isset($this->request->named['Product.brand_id']) && $brands = $this->request->named['Product.brand_id']) {
			$brand_ids = array_keys($this->_getBrandOptions(explode(' ', $brands)));
			$this->set('brandsFilterValue', $brand_ids);
			unset($this->request->params['named']['Product.brand_id']);
		}
		if (!$brand_ids) {
			$brand_ids = array(0);
		}
		$this->paginate['conditions']['Product.brand_id'] = $brand_ids;

        if (isset($this->request->named['Product.id'])) {
			$idList = array();
			if (strpos($this->request->named['Product.id'], ',')) {
				$idList = explode(',', $this->request->named['Product.id']);
			} elseif ($this->request->named['Product.id'] == 'list') {
				$file = Configure::read('tmp_dir').'user_products_'.$this->Auth->user('id').'.tmp';
				$idList = explode("\n", str_replace("\r\n", "\n", file_get_contents($file)));
				unlink($file);
			}

			if ($idList) {
				$this->paginate['conditions']['Product.id'] = $idList;
				unset($this->request->params['named']['Product.id']);
			}
        }

		if (!Configure::read('Settings.show_fake')) {
			$this->paginate['conditions']['Product.is_fake'] = 0;
		} else if (isset($this->request->named['Product.is_fake'])) {
			$this->paginate['conditions']['Product.is_fake'] = $this->request->named['Product.is_fake'];
			$this->set('isFakeFilterValue', $this->request->named['Product.is_fake']);
			unset($this->request->params['named']['Product.is_fake']);
		}
    }

	public function printXls() {
		if ($this->request->is(array('put', 'post'))) {
			ignore_user_abort(true);
			set_time_limit(0);

			$this->layout = 'print_xls';
			$this->_processParams();

			if ($brands = $this->request->data('brandID')) {
				$conditions = array('brand_id' => explode(',', $brands), 'is_fake' => 0);
				if ($this->request->data('nonZeroAmount')) {
					$conditions['AND'] = array('OR' => $this->skladOstatki);
				}

				$this->Product->unbindModel(array(
					'belongsTo' => array('Category', 'Subcategory', 'Brand'),
					'hasOne' => array('Media', 'Seo', 'Search')
				));
				$aRowset = $this->Product->find('all', compact('conditions'));
			} elseif ($this->request->data('aID')) {
				$aID = explode(',', $this->request->data('aID'));
				// $this->paginate['fields'][] = 'Product.cat_id'; уже добавлено
				$this->paginate['fields'][] = 'Product.subcat_id';
				// $this->paginate['fields'][] = 'Product.brand_id'; уже добавлено
				$this->paginate['conditions'] = array('Product.id' => $aID);
				$this->paginate['order'] = 'FIELD (Product.id, ' . $this->request->data('aID') . ') ASC';
				$this->paginate['limit'] = count($aID);
				$aRowset = $this->PCTableGrid->paginate('Product');
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

			$this->set(compact('aRowset', 'aCategories', 'aSubcategories', 'aBrands'));
		} else {
			$this->redirect(array('action' => 'index'));
		}
	}

	private function processFilter($value) {
		// очищаем от лишних пробелов
		$_value = $this->Search->stripSpaces(mb_strtolower($value));

		// если ввели только номер - поиск по номерам
		$aWords = explode(' ', $_value);
		if (count($aWords) == 1 && $this->DetailNum->isDigitWord($value)) {
			$this->processNumber($value);
			return;
		}
		$aWords = $this->Search->processTextRequest($_value);
		$this->paginate['conditions']['Search.body LIKE '] = '%'.implode('%', $aWords).'%';
		if ($this->Search->isRu($_value)) {
			$this->paginate['order'] = 'Product.title_rus LIKE "'.$_value.'%" DESC';
		} else {
			$this->paginate['order'] = 'Product.title LIKE "'.$_value.'%" DESC';
		}
	}

	private function processNumber($detail_num) {
		$_detail_num = $this->DetailNum->strip($detail_num);
		$q = str_replace(array('*', '~'), '', $this->request->named['Product.detail_num']);
		$this->searchDetail = array('q' => $q, 'detail_num' => $_detail_num) ; // сохраняем оригинальный запрос
		$product_ids = $this->DetailNum->findDetails($this->DetailNum->stripList('*'.$_detail_num.'*'), true);
		if ($this->DetailNum->isReachLimit()) {
			$this->setFlash(__('Too many products. Try to search by more exact keyword'), 'error');
		}
		$this->paginate['conditions'] = array('Product.id' => $product_ids);

		$order = array(
			"Product.code = '{$detail_num}' AND Product.detail_num = '{$detail_num}' DESC",
			"Product.code = '{$detail_num}' DESC",
			"Product.code = '{$_detail_num}' DESC",
			"Product.detail_num LIKE '%{$detail_num}%' DESC",
			"Product.detail_num LIKE '%{$_detail_num}%' DESC"
		);
		foreach ($product_ids as $id) {
			$order[] = 'Product.id = ' . $id . ' DESC';
		}
		$this->paginate['order'] = implode(', ', $order);
	}

	private function _isGridFilter() {
		if ($this->request->action == 'printXls') {
			return true;
		}
		foreach($this->request->named as $key => $val) {
			if (strpos($key, 'Product.') !== false || strpos($key, 'PMFormData.') !== false) {
				return true;
			}
		}
		return false;
	}

    public function index() {
    	set_time_limit(60 * 5); //
		$this->Product->unbindModel(array(
			'belongsTo' => array('Category', 'Subcategory', 'Brand'),
			'hasOne' => array('Seo', 'Media')
		), false);
		// $this->Product->belongsTo = false;
		$this->searchDetail = array();
		$lFlag = $this->_isGridFilter();
		$this->_processParams();

		if (!$lFlag) {
			// вырезать связь с PMFormData для ускорения
			$this->Product->unbindModel(array(
				'hasOne' => array('PMFormData', 'Search')
			), false);
		}
        $aRowset = $this->PCTableGrid->paginate('Product');
		if (!$lFlag) {
			// добавить данные отдельным запросом
			$fields = array_merge($this->aFields, array('object_id'));
			$conditions = array('object_type' => 'ProductParam', 'object_id' => Hash::extract($aRowset, '{n}.Product.id'));
			$formData = $this->PMFormData->find('all', compact('fields', 'conditions'));
			$formData = Hash::combine($formData, '{n}.PMFormData.object_id', '{n}.PMFormData', '{n}.PMFormData.id');
			foreach($aRowset as &$row) {
				$row['PMFormData'] = $formData[$row['Product']['id']];
			}
		}
        $this->set('aRowset', $aRowset);

		$aCategories = $this->Category->findAllById(array_unique(Hash::extract($aRowset, '{n}.Product.cat_id')));
		$aCategories = Hash::combine($aCategories, '{n}.Category.id', '{n}.Category');
		$this->set('aCategories', $aCategories);

		$product_ids = Hash::extract($aRowset, '{n}.Product.id');
		$aProductMedia = $this->Media->getList(
			array('media_type' => 'image', 'object_type' => 'Product', 'object_id' => $product_ids, '(show_ru + show_by) > 0'),
			array('main_ru + main_by' =>  'DESC', 'main_by' => 'DESC', 'main_ru' => 'DESC', 'id' => 'DESC')
		);
		$aProductMedia = Hash::combine($aProductMedia, '{n}.Media.id', '{n}', '{n}.Media.object_id');
		$this->set('aProductMedia', $aProductMedia);

        $brand_ids = array_unique(Hash::extract($aRowset, '{n}.Product.brand_id'));

		$aBrandMedia = $this->Media->getList(array('media_type' => 'image', 'object_type' => 'Brand', 'object_id' => $brand_ids, 'main_by' => 1));
        $aBrandMedia = Hash::combine($aBrandMedia, '{n}.Media.object_id', '{n}');
		$this->set('aBrandMedia', $aBrandMedia);

        $field = $this->PMFormField->findByLabel('Мотор');
        $this->set('motorOptions', $field);
		$this->set('aBrandOptions', $this->_getBrandOptions());

		// добавить историю поиска по номерам
		if ($this->searchDetail) { // был фильтр по номеру
			$this->loadModel('SearchHistory');
			$this->SearchHistory->processSearch($this->searchDetail['q'], $this->searchDetail['detail_num'], $aRowset, $this->currUser('id'));
		}
	}

	public function edit($id = 0) {
		if (!$this->isAdmin()) {
			return $this->redirect(array('action' => 'index'));
		}

		$aKurs = $this->PMFormConst->findAllByIsPriceKurs(1);
		$this->set('aKurs', $aKurs);
		$fields = $this->PMFormField->getObjectList('SubcategoryParam', '', 'PMFormField.sort_order');

		if (!$id) {
			// выставляем типы для записей
			$this->request->data('Product.object_type', $this->Product->objectType);
			$this->request->data('Seo.object_type', $this->Product->objectType);
			$this->request->data('PMFormData.object_type', 'ProductParam');
		}

		$remain = 0;
		if ($this->request->is(array('post', 'put'))) {
			$this->request->data('Product.motor', $this->request->data('PMFormData.fk_6'));

			// Пересчет движения отстаток для отчета
			$a1_val = 0; $a2_val = 0;
			$a1 = 'PMFormData.fk_'.Configure::read('Params.A1');
			$a2 = 'PMFormData.fk_'.Configure::read('Params.A2');
			if ($id) {
				$product = $this->Product->findById($id);
				$a1_val = intval(Hash::get($product, $a1));
				$a2_val = intval(Hash::get($product, $a2));
			}
			$remain = (intval($this->request->data($a1)) - $a1_val) + (intval($this->request->data($a2)) - $a2_val);
			$priceData = array(); // $this->request->data('FormPrice');
			if ($id) {
				foreach ($this->request->data('FormPrice') as $fk_id => $priceRow) {
					if ($priceRow['price']) {
						$priceData[$fk_id] = $priceRow;
					}
				}
			}
		}

		$old_id = $id;
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$product_id = $id;
			if ($remain) {
				$this->ProductRemain->save(compact('product_id', 'remain'));

				// скорректировать статистику за год
				$field = 'fk_'.Configure::read(($remain > 0) ? 'Params.incomeY' : 'Params.outcomeY');
				$this->PMFormData->saveField($field, intval($this->PMFormData->field($field)) + $remain); // уже выставлен нужный $this->PMFormData->id
			}

			$this->FormPrice->deleteAll(compact('product_id'));
			foreach ($priceData as $fk_id => $row) {
				$row['product_id'] = $id;
				$this->FormPrice->clear();
				$this->FormPrice->save($row);
			}
			$this->PMFormData->recalcFormula($this->PMFormData->id, $fields);

			if (!$old_id) {
				$category = $this->Category->findById($this->request->data('Product.cat_id'));
				$this->_cleanCache('product_Categories.xml');
				$this->_cleanProductsCache($category);
			}

			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}

		$fieldsAvail = array();
		foreach($fields as $_field) {
			$_field_id = $_field['PMFormField']['id'];
			if ($_field['PMFormField']['field_type'] != FieldTypes::FORMULA) {
				if (!$id) {
					if ($_field['PMFormField']['field_type'] == FieldTypes::INT) {
						$this->request->data('PMFormData.fk_'.$_field_id, '0');
					} elseif ($_field['PMFormField']['field_type'] == FieldTypes::FLOAT) {
						$this->request->data('PMFormData.fk_'.$_field_id, '0.00');
					}
				} else {
					if ($_field['PMFormField']['field_type'] == FieldTypes::PRICE) {
						$_field['PMFormField'] = $this->PMFormField->unpackOptions($_field['PMFormField']);
					}
				}
				$fieldsAvail[] = $_field;
			}
		}
		$this->set('form', $fieldsAvail);

		$this->set('aCategories', $this->Category->getOptions('Category'));
		$this->set('aSubcategories', $this->Subcategory->find('all', array(
			'fields' => array('id', 'object_id', 'title', 'Category.id', 'Category.title'),
			'order' => 'object_id'
		)));

		$this->set('aBrandOptions', $this->aBrandOptions);

		$this->loadModel('Currency');
		$this->set('aCurrency', $this->Currency->getOptions());
		$this->set('xPrices', $this->FormPrice->getProductPrices($id));

		if (!$id) {
			// выставляем значения по умолчанию
			$this->request->data('Product.status', array('published', 'active', 'show_detailnum'));
			$this->request->data('Product.count', '0');
			$this->request->data('Product.cat_id', 2133); // category = DEUTZ
			$this->request->data('Product.subcat_id', 2146); // subcategory = DEUTZ 1013
			$this->request->data('Product.brand_id', 2166); // brand = Deutz
		}
	}

	public function delete($id = '')
	{
		ignore_user_abort(true);
		set_time_limit(0);

		$brand_id = $this->request->query('brand_id');
		$motor = $this->request->query('motor');
		$conditions = array();
		if ($brand_id || $motor) {
			$ors = array();
			if ($brand_id) {
				$conditions[] = array('Product.brand_id' => explode(',', $brand_id));
			}
			if ($motor) {
				foreach(explode(',', $motor) as $_motor) {
					$ors[] = 'PMFormData.fk_' . Configure::read('Params.motor') . ' LIKE "%' . $_motor . '%"';
				}
				$conditions[] = array('OR' => $ors);
			}
		} elseif ($id) {
			$ids = explode(',', $id);
			$conditions = array('Product.id' => $ids);
		}

		if ($conditions) {
			$total = $this->Product->find('count', compact('conditions'));
			try {
				$this->Product->trxBegin();
				$this->Product->deleteAll($conditions, true, true);

				// clean up cache for categories fof removed products
				$group = array('Product.cat_id');
				$fields = array('Product.cat_id');
				$order = array('Product.cat_id' => 'ASC');
				$products = $this->Product->find('all', compact('fields', 'conditions', 'group', 'order'));
				$cat_ids = Hash::extract($products, '{n}.Product.cat_id');
				$aCategories = $this->Category->findAllById($cat_ids);
				// $this->_cleanCache('product_Categories.xml');
				foreach($aCategories as $category) {
					$this->_cleanProductsCache($category);
				}

				$this->Product->trxCommit();
				$this->setFlash(__('%s products have been deleted', $total), 'success');
			} catch (Exception $e) {
				$this->setFlash(__('Process execution error! %s', $e->getMessage()), 'error');
			}

		}

		if ($backURL = $this->request->query('backURL')) {
			$this->redirect($backURL);
			return;
		}
		$this->redirect(array('controller' => 'AdminProduct', 'action' => 'index'));
	}
}
