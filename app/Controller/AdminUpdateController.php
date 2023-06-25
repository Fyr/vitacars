<?php
App::uses('AdminController', 'Controller');
class AdminUpdateController extends AdminController {
    public $name = 'AdminUpdate';
    public $layout = false;

	// public $components = array('Gearman.Gearman');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
		$this->autoRender = false;
	}
	
	public function update3() {
		set_time_limit(60 * 10);
		App::uses('Translit', 'Article.Vendor');
		// $this->PHTranslit = new PHTranslitHelper($this->view);
		
		$this->loadModel('SiteArticle');
		$conditions = array('object_type' => 'Product');
		$fields = array('id', 'title_rus', 'code');
		$page = 1;
		$limit = 1000;
		$order = 'SiteArticle.id'; 
		$count = 0;
		$data = array();
		while ($articles = $this->SiteArticle->find('all', compact('fields', 'conditions', 'page', 'limit', 'order'))) {
			$page++;
			foreach($articles as $article) {
				$count++;
				$data = $article['SiteArticle'];
				$data['page_id'] = Translit::convert($data['title_rus'].'-'.$data['code'], true);
				$this->SiteArticle->save(array('id' => $data['id'], 'page_id' => $data['page_id']));
			}
		}
		exit($count.' products processed');
	}
	
	public function update4() {
		$this->loadModel('Form.PMFormField');
		$this->loadModel('Form.PMFormValue');
		$this->loadModel('Form.PMFormField');
		$this->loadModel('Form.PMFormData');
		$this->loadModel('SiteArticle');
		
		// пересохраняем поля, чтобы создать form_data
		$aRowset = Hash::combine($this->PMFormField->find('all'), '{n}.FormField.id', '{n}.FormField');
		foreach($aRowset as $formField) {
			if (!FieldTypes::getSqlTypes($formField['field_type'])) {
				echo 'Error! Unexisted field type: '.$formField['id'].' - '.$formField['field_type'].'<br>';
				$formField['field_type'] = FieldTypes::STRING;
			}
			$this->PMFormField->save($formField, array('forceCreate' => true));
		}
		echo 'Processed '.count($aRowset).' form fields<br>';
		
		$conditions = array('object_type' => 'Product');
    	$page = 1;
    	$limit = 10;
    	$order = 'SiteArticle.id'; 
    	$count = array('Product' => 0, 'Param' => 0);
    	while ($articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'))) {
    		$page++;
    		foreach($articles as $article) {
    			$count['Product']++;
    			$id = $article['SiteArticle']['id'];
    			$formValues = $this->PMFormValue->getValues('ProductParam', $id);
    			$formData = Hash::combine($formValues, '{n}.PMFormValue.field_id', '{n}.PMFormValue.value');
    			$data = array('object_type' => 'ProductParam', 'object_id' => $id);
    			foreach($aRowset as $id => $field) {
    				$count['Param']++;
    				if (isset($formData[$id])) {
    					$data['fk_'.$id] = $formData[$id];
    					
    					$data['fk_'.$id] = str_replace('&nbsp;', '', $data['fk_'.$id]);
    					if ($field['field_type'] == FieldTypes::INT) {
    						$data['fk_'.$id] = intval($data['fk_'.$id]);
    					} elseif ($field['field_type'] == FieldTypes::FLOAT) {
    						if ($id == 10) {
    							if (trim($data['fk_'.$id]) === '') {
    								$data['fk_'.$id] = 0.01;
    							} 
    						}
    						$data['fk_'.$id] = floatval(str_replace(',', '.', $data['fk_'.$id]));
    					}
    				}
    			}
    			$this->PMFormData->clear();
    			$this->PMFormData->save(array('PMFormData' => $data));
			}
    	}
		echo 'Processed '.$count['Product'].' products, '.$count['Param'].' params <br>';
	}

	public function update5() {
		App::uses('Path', 'Core.Vendor');
		$pathInfo = Path::dirContent(Configure::read('import.folder'));
		foreach($pathInfo['files'] as $file) {
			$fullPath = Configure::read('import.folder').$file;
			$path = explode(DS, $this->_getFilePath($file));
			
			$_path = Configure::read('import.folder').DS.$path[0];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[1];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[2];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			rename($fullPath, $_path.DS.$file);
		}
		
		$this->autoRender = false;
		echo 'Processed '.count($pathInfo['files']).' files';
	}
	
	private function _getFilePath($file) {
		list($fileDate) = explode('_', str_replace('dlt_mgr', '', $file));
		$path = substr($fileDate, 0, 4).DS.substr($fileDate, 4, 2).DS.substr($fileDate, 6, 2);
		return $path;
	}

	public function update6() {
		ignore_user_abort(true);
		set_time_limit(0);
		$this->autoRender = false;
		$this->loadModel('Product');
		$this->loadModel('DetailNum');
		$fields = array('id', 'detail_num');
		$conditions = array('object_type' => 'Product', 'processed' => 0);
		$page = 1;
		$limit = 1000;
		$order = array('Product.id');
		$recursive = -1;
		$count_rows = 0;
		$count_nums = 0;
		while (file_get_contents('cont.log') && $rows = $this->Product->find('all', compact('fields', 'conditions', 'page', 'limit', 'order', 'recursive'))) {
			$page++;
			foreach($rows as $row) {
				$detail_nums = $this->DetailNum->stripList($row['Product']['detail_num']); // explode(',', str_replace(array('   ', '  ', ' '), ' ', trim($row['Product']['detail_num'])));
				fdebug($row, 'products.log');
				$count_rows++;
				foreach($detail_nums as $dn) {
					$dn = $this->DetailNum->strip($dn);
					if (!$this->DetailNum->findByProductIdAndDetailNum($row['Product']['id'], $dn)) {
						fdebug("{$dn}\r\n", 'detail_nums.log');
						$count_nums++;
						$this->DetailNum->clear();
						$this->DetailNum->save(array('detail_num' => mb_strtolower($dn), 'product_id' => $row['Product']['id'], 'num_type' => DetailNum::ORIG));
					}
				}
				$this->Product->clear();
				$this->Product->save(
					array('id' => $row['Product']['id'], 'processed' => 1),
					array('callbacks' => false)
				);
			}
		}
		echo "Processing finished. Rows: {$count_rows}, nums: {$count_nums}";
	}

	public function update7() {
		ignore_user_abort(true);
		set_time_limit(0);
		$this->loadModel('DetailNum');
		$this->loadModel('Form.PMFormData');
		$fields = array('id', 'object_id', 'fk_60');
		$conditions = array('fk_60 IS NOT NULL'); //
		$page = 1;
		$limit = 1000;
		$order = array('object_id');
		$recursive = -1;
		$count_rows = 0;
		$count_nums = 0;
		while ($rows = $this->FormData->find('all', compact('fields', 'conditions', 'page', 'limit', 'order', 'recursive'))) {
			$page++;
			foreach($rows as $row) {
				$detail_nums = trim($row['FormData']['fk_60']);
				$detail_nums = str_replace(array("\r\n", "\r", "\n"), ',', $detail_nums); // разделяем строки номеров
				$detail_nums = str_replace(array('   ', '  ', ' '), ',', $detail_nums);
				$detail_nums = explode(',', $detail_nums);
				$count_rows++;

				foreach($detail_nums as $dn) {
					$dn = trim($dn);
					if ($dn && $this->DetailNum->isDigitWord($dn)) {
						$dn = $this->DetailNum->strip($dn);
						if ($r = $this->DetailNum->findByProductIdAndDetailNum($row['FormData']['object_id'], $dn)) {
						} else {
							fdebug("{$dn}\r\n", 'detail_nums_cross.log');
							$count_nums++;
							$this->DetailNum->clear();
							$this->DetailNum->save(array('detail_num' => mb_strtolower($dn), 'product_id' => $row['FormData']['object_id'], 'num_type' => DetailNum::CROSS));
						}
					}
				}
			}
		}
		echo "Processing finished. Rows: {$count_rows}, nums: {$count_nums}";
	}



/*
	public function statusUpdate6() {
		$this->autoRender = false;
		$this->loadModel('Product');
		$conditions = array();
		$recursive = -1;
		$total = $this->Product->find('count', compact('conditions', 'recursive'));

		$conditions['processed'] = 1;
		$proc = $this->Product->find('count', compact('conditions', 'recursive'));
		echo "{$proc} / {$total} (".round($proc / $total * 100, 1)."%)";
	}
*/

    public function update8() {
		$this->autoRender = true;
		$this->layout = 'print_xls';
		ignore_user_abort(true);
		set_time_limit(0);

		$this->loadModel('Form.PMFormField');
		$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		$this->set('aParams', $aParams);
		$aLabels = array();
		foreach($aParams as $id => $_field) {
			$alias = 'PMFormData.fk_'.$id;
			$aLabels[$alias] = $_field['PMFormField']['label'];
		}
		$this->set('aLabels', $aLabels);

		$this->loadModel('Product');
		$this->loadModel('Form.PMFormData');
		$sql = "SELECT code, COUNT(*) AS count FROM articles WHERE object_type = 'Product' AND code != '' GROUP BY code HAVING count > 1";
		$res = $this->Product->query($sql);
		$codes = Hash::extract($res, '{n}.articles.code');
		$aProducts = array();
		$aDeleted = array();
		$deleted = 0;
		foreach($codes as $i => $code) {
			$products = $this->Product->findAllByObjectTypeAndCode('Product', $code, null, 'Product.id');
			$product = array_shift($products);
			$aProducts[] = $product;
			$aDeleted[$code] = $products;
			$products = array_reverse($products);

			$lFlag = false;
			$aFK = array();
			foreach($products as $_product) {

				foreach($_product['PMFormData'] as $fk_id => $_val) {
					if (strpos($fk_id, 'fk_') !== false) {
						$val = (is_numeric($product['PMFormData'][$fk_id])) ? floatval($product['PMFormData'][$fk_id]) : trim($product['PMFormData'][$fk_id]);
						$_val = (is_numeric($_val)) ? floatval($_val) : trim($_val);
						if (!$val && $_val) {
							$product['PMFormData'][$fk_id] = $_val;
							$lFlag = true;
							$aFK[] = 'PMFormData.'.$fk_id;
						}
					}
				}
				$this->Product->delete($_product['Product']['id']);
			}
			if ($lFlag) {
				$aData[$code] = array('fks' => $aFK, 'data' => $product['PMFormData']);
				$this->PMFormData->save($product['PMFormData']);
			}
		}
		$this->set(compact('aProducts', 'aDeleted', 'aData'));

		$this->loadModel('Category');
		$this->loadModel('Subcategory');
		$this->loadModel('Brand');
		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.cat_id'));
		$conditions = array('Category.object_type' => 'Category', 'Category.id' => $ids);
		$aCategories = $this->Category->find('list', compact('conditions'));

		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.subcat_id'));
		$conditions = array('Subcategory.object_type' => 'Subcategory', 'Subcategory.id' => $ids);
		$aSubcategories = $this->Subcategory->find('list', compact('conditions'));

		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.brand_id'));
		$conditions = array('Brand.object_type' => 'Brand', 'Brand.id' => $ids);
		$aBrands = $this->Brand->find('list', compact('conditions'));

		$this->set(compact('aCategories', 'aSubcategories', 'aBrands'));
	}

	public function testMedia() {
		$this->loadModel('Media.Media');
		$this->_resetDB();

		// Тест на установку флага main, если устанавливается 1 флаг для показа
		$this->Media->update(1865, array('show_ru' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false)),
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 1.1. Set main if 1 media is shown', $aRows, $aExpect);

		$this->Media->update(1864, array('show_by' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 1.2. Set main if 1 media is shown', $aRows, $aExpect);

		$this->Media->update(1865, array('show_by' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => true, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 2.1 Set shown media normally', $aRows, $aExpect);

		$this->Media->update(1866, array('show_ru' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => true, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 2.2 Set shown media normally', $aRows, $aExpect);

		$this->Media->update(1865, array('show_ru' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 3.1 Reset main flag if shown flag is reset (set main for shown)', $aRows, $aExpect);

		$this->Media->update(1865, array('show_by' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 3.2 Reset main flag if shown flag is reset (main flag remains for shown)', $aRows, $aExpect);

		$this->Media->update(1864, array('show_by' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 4.1 Reset all main flags if no shown media', $aRows, $aExpect);

		$this->Media->update(1866, array('show_ru' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 4.2 Reset all main flags if no shown media', $aRows, $aExpect);

		//$this->_resetDB();

	}

	private function _resetDB() {
		foreach(Configure::read('domains') as $lang) {
			$this->Media->updateAll(array('show_'.$lang => 0, 'main_'.$lang => 0));
		}
	}

	public function update9() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('DeutzParser', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'DeutzParser');
			$this->Task->runBkg($id);
			$this->redirect(array('action' => 'update9'));
		}
	}

	public function update10() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('ProductParser', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'ProductParser');
			$this->Task->runBkg($id);
			$this->redirect(array('action' => 'update10'));
		}
	}

	public function update11() {
		$this->loadModel('OrderProduct');
		$aRowset = $this->OrderProduct->find('all');
		try {
			$this->OrderProduct->trxBegin();
			$aRowset = Hash::combine($aRowset, '{n}.OrderProduct.id', '{n}.OrderProduct', '{n}.OrderProduct.order_id');
			foreach ($aRowset as $order_id => $orderDetail) {
				$nn = 0;
				$number = '';
				foreach ($orderDetail as $id => $detail) {
					if ($number <> $detail['number']) {
						$nn++;
						$number = $detail['number'];
					}
					$this->OrderProduct->clear();
					$this->OrderProduct->save(compact('id', 'nn'));
				}
			}
			$this->OrderProduct->trxCommit();
		} catch (Exception $e) {
			$this->OrderProduct->trxRollback();
			echo $e->getMessage();
		}
	}

	public function update12() {
		App::uses('CsvReader', 'Vendor');
		$this->loadModel('SqlStats');
		$this->loadModel('WebStats');
		$project = 'vcars';
		try {
			$this->SqlStats->trxBegin();
			$keys = array('db_name', 'qty', 'q_time', 'url');
			$data = CsvReader::parse('sql_stats.log', array('keys' => $keys, 'csv_div' => ','));
			foreach ($data['data'] as $row) {
				$row['project'] = $project;
				$row['q_time'] = $row['q_time'] / 1000;
				$this->SqlStats->clear();
				$this->SqlStats->save($row);
			}
			$this->SqlStats->trxCommit();
			echo 'SQL stats: Processed '.count($data['data']).' rows<br/>';
		} catch (Exception $e) {
			$this->SqlStats->trxRollback();
			echo 'SQL stats error: '.$e->getMessage().'<br/>';
		}
		try {
			$this->WebStats->trxBegin();
			$keys = array('q_time', 'url');
			$data = CsvReader::parse('web_stats.log', array('keys' => $keys, 'csv_div' => ','));
			foreach ($data['data'] as $row) {
				$row['project'] = $project;
				$this->WebStats->clear();
				$this->WebStats->save($row);
			}
			$this->WebStats->trxCommit();
			echo 'WWW stats: Processed '.count($data['data']).' rows<br/>';
		} catch (Exception $e) {
			$this->WebStats->trxRollback();
			echo 'WWW stats error: '.$e->getMessage().'<br/>';
		}
	}

	public function update13() {
		$this->layout = 'admin';
		$code = 4207534;
		$this->loadModel('Product');
		$this->Product->unbindModel(array(
			'belongsTo' => array('Category', 'Subcategory', 'Brand'),
			'hasOne' => array('Media', 'Seo', 'Search', 'PMFormData')
		));
		$fields = array('Product.id');
		$product = $this->Product->findByCode($code, $fields);

		$this->loadModel('Form.PMFormData');
		$fields = array('PMFormData.id');
		$formData = $this->PMFormData->findByObjectTypeAndObjectId('ProductParam', $product['Product']['id'], $fields);
		// $this->PMFormData->save(array('id' => $product['PMFormData']['id'], $key => $val));
		$this->autoRender = true;
	}

	public function testCsv() {
		ignore_user_abort(true);
		set_time_limit(0);
		echo '<pre>';
		App::uses('CsvReader', 'Vendor');
		print_r(CsvReader::parse('data.csv'));
		// print_r(CsvReader::parse('data2.csv', array('keys' => array('detail_num', 'qty'))));
		echo '</pre>';
	}

	public function update14() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('VelesParser', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'VelesParser');
			$this->Task->runBkg($id);
			$this->redirect(array('action' => 'update14'));
		}
	}

	public function update15() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('CrossCsvParser', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'CrossCsvParser');
			$this->Task->runBkg($id);
			sleep(1);
			$this->redirect(array('action' => 'update15'));
		}
	}

	public function update16()
	{
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('UpdateProducts', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'UpdateProducts');
			$this->Task->runBkg($id);
			sleep(1);
			$this->redirect(array('action' => 'update16'));
		}
	}

	public function update17() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$process_brand_id = 2166;
		$aAllowedBrands = array('CLAAS', 'FENDT', 'O & K', 'TEREX FUCHS', 'DEUTZ', 'VOLVO', 'ATLAS COPCO', 'BOMAG');

		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('CreateFakeProducts', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'CreateFakeProducts', array(
				'parse_brand_id' => $process_brand_id, 
				'allow_brands' => $aAllowedBrands
			));
			$this->Task->runBkg($id);
			sleep(1);
			$this->redirect(array('action' => 'update17')); 
		}
	}
}
