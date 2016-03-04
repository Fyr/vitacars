<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('DetailNum', 'Model');
App::uses('PMFormValue', 'Form.Model');
App::uses('PMFormField', 'Form.Model');
class AdminUploadCsvController extends AdminController {
    public $name = 'AdminUploadCsv';
    public $layout = 'admin';
    public $uses = array('Product', 'Form.PMFormData', 'Form.PMFormField', 'Brand', 'Category', 'Subcategory', 'Seo.Seo', 'ProductRemain', 'DetailNum');
    
    const CSV_DIV = ';';
    private $errLine = 0, $errLog;
    
	public function beforeFilter() {
		/*
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		*/
		parent::beforeFilter();
	}
    
	public function index() {
		set_time_limit(60 * 10);
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name']) {
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				
				$fieldRights = $this->_getFieldRights();
				$keyField = 'code';
				foreach($aData['keys'] as $fk_id) {
					$f_id = str_replace('fk_', '', $fk_id);
					if (!in_array($fk_id, array('detail_num', 'code')) && !($fieldRights && in_array($f_id, $fieldRights))) {
						throw new Exception(__('You have no access rights to load `%s`', $fk_id));
					}
					if ($fk_id == 'detail_num') {
						$keyField = 'detail_num'; // в первую очередь проверяем по detail_num, если есть и detail_num, и code
					}
				}
				
				$this->Product->getDataSource()->begin();
				$aID = $this->_updateParams($aData['keys'], $this->_getCounters($keyField, $aData['data']));
				$this->Product->getDataSource()->commit();
				
				$this->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
				$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index')); // , 'Product.id' => implode(',', $aID)
			}
		} catch (Exception $e) {
			$this->Product->getDataSource()->rollback();
			$this->setFlash(__($e->getMessage(), $this->errLine), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'index'));
		}
	}
	
	/**
	 * Получить данные из CSV файла в виде ассоц.массива 
	 *
	 * @param str $file
	 * @return array
	 */
	private function _parseCsv($file) {
		$file = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$file = str_replace("\r\n", "\n", $file);
		$file = str_replace(array('   ', '  '), ' ', $file);
		$file = explode("\n", $file);
		if (!($file && is_array($file) && count($file) > 1)) {
			throw new Exception('Incorrect file content');
		}
		
		$keys = explode(self::CSV_DIV, trim($file[0]));
		unset($file[0]);
		
		$aData = array();
		$this->errLine = 1;
		foreach($file as $row) {
			$this->errLine++;
			$_row = explode(self::CSV_DIV, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception('Incorrect file format (Line %s)');
			}
			$aData[] = array_combine($keys, $_row);
		}
		
		return array('keys' => $keys, 'data' => $aData);
	}
	
	/**
	 * Проинициализировать счетчики в зав-ти от ID продукта
	 *
	 * @param unknown_type $aData
	 */
	private function _getCounters($keyField = 'detail_num', $aData) {
		$aParams = array();
		foreach($aData as $row) {
			list($number) = array_values($row);
			$ids = array();
			if ($keyField == 'detail_num') {
				$conditions = array('detail_num' => trim($number));
				$ids = $this->DetailNum->find('all', compact('conditions'));
				$ids = Hash::extract($ids, '{n}.DetailNum.product_id');
				$ids = array_unique($ids);
			} else {
				$fields = array('Product.id');
				$conditions = array('Product.code' => $number);
				$ids = $this->Product->find('all', compact('fields', 'conditions'));
				$ids = Hash::extract($ids, '{n}.Product.id');
			}
			array_shift($row); // исключить 1й ключ из обрабатываемой строки (номер детали)
			foreach($ids as $object_id) {
				if (!isset($aParams[$object_id])) {
					$aParams[$object_id] = array();
				}
				foreach($row as $counter => $count) {
					if (isset($aParams[$object_id][$counter])) {
						$aParams[$object_id][$counter]+= floatval($count);
					} else {
						$aParams[$object_id][$counter] = floatval($count);
					}
				}
			}
		}
		return $aParams;
	}
	
	/**
	 * Обновить счетчики по ID продукта
	 *
	 * @param array $aParams
	 */
	private function _updateParams($keys, $aParams) {
		// Считать инфу о колонках
		array_shift($keys); // исключить 1й ключ из обрабатываемой строки (номер детали)
		$aKeys = array();
		$aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		foreach($keys as $id) {
			if (strpos($id, 'fk_') !== false && !in_array(intval(str_replace('fk_', '', $id)), array_keys($aFormFields))) {
				throw new Exception(__('Incorrect field ID %s', $id));
			}
			$aKeys[$id] = 0;
		}
		
		$aID = array();
		// перед сохранением очистить столбцы
		// $this->PMFormData->updateAll($aKeys);
		$a1 = 'fk_'.Configure::read('Params.A1');
		$a2 = 'fk_'.Configure::read('Params.A2');
		foreach($aParams as $object_id => $counters) {
			$product = $this->Product->findById($object_id);
			if (!$product) {
				throw new Exception(__('Product %s not found', 'Product.ID='.$object_id));
			}
			/*
			$formData = $this->PMFormData->getObject('ProductParam', $object_id);
			if (!$formData) {
				throw new Exception(__('Product %s not found', 'FormData.object_id='.$object_id));
			}
			*/
			$remain = 0;
			if (in_array($a1, $keys) || in_array($a2, $keys)) {
				$a1_val = intval(Hash::get($product, 'PMFormData.'.$a1));
				$a2_val = intval(Hash::get($product, 'PMFormData.'.$a2));
				
				$a1_new = intval((isset($counters[$a1]) && $counters[$a1]) ? $counters[$a1] : $a1_val);
				$a2_new = intval((isset($counters[$a2]) && $counters[$a2]) ? $counters[$a2] : $a2_val);
				
				$remain = ($a1_new - $a1_val) + ($a2_new - $a2_val);
			}
			
			$counters['id'] = $this->PMFormData->id = $product['PMFormData']['id'];
			if (!$this->PMFormData->save($counters)) {
				throw new Exception(__('Product params could not be saved: %s', print_r($counters, true)));
			}
			if ($remain) {
				$product_id = $object_id;
				$this->ProductRemain->clear();
				$this->ProductRemain->save(compact('product_id', 'remain'));
				
				// скорректировать статистику за год
				$field = 'fk_'.Configure::read(($remain > 0) ? 'Params.incomeY' : 'Params.outcomeY');
				$this->PMFormData->saveField($field, intval($this->PMFormData->field($field)) + $remain); // уже выставлен нужный $this->PMFormData->id
			}
			$this->PMFormData->recalcFormula($this->PMFormData->id, $aFormFields);
			$aID[] = $object_id;
		}
		
		$outcomeY = 'fk_'.Configure::read('Params.outcomeY');
		if (in_array($a1, $keys) || in_array($a2, $keys)) {
			$fields = array_merge(array('id', 'object_id', $outcomeY), $keys);
		}
	
    	$conditions = array('object_type' => 'ProductParam', 'NOT' => array('object_id' => array_keys($aParams)));
    	$page = 1;
    	$limit = 100;
    	$order = array('object_id');
    	while ($rows = $this->PMFormData->find('all', compact('fields', 'conditions', 'page', 'limit', 'order'))) {
    		$page++;
    		$remain = 0;
    		foreach($rows as $row) {
    			$data = array_merge(array('id' => $row['PMFormData']['id']), $aKeys);
    			
    			if (in_array($a1, $keys) || in_array($a2, $keys)) {
	    			$remain = -intval(Hash::get($row, 'PMFormData.'.$a1)) - intval(Hash::get($row, 'PMFormData.'.$a2));
	    			if ($remain) {
	    				$product_id = $row['PMFormData']['object_id'];
	    				$this->ProductRemain->clear();
						$this->ProductRemain->save(compact('product_id', 'remain'));
	    				
	    				$data[$outcomeY] = $row['PMFormData'][$outcomeY] + $remain;
	    			}
    			}
    			$this->PMFormData->save($data);
    			$this->PMFormData->recalcFormula($this->PMFormData->id, $aFormFields);
    			$aID[] = $row['PMFormData']['object_id'];
    		}
    	}
		
		return $aID;
	}

	protected function addErrLog($err) {
		$this->errLog.= $err.'<br>';
	}

	protected function _checkCreatedProducts($aData) {
		$this->errLine = 1;

		$this->errLog = '';
		$aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		foreach($aData['keys'] as $id) {
			if (strpos($id, 'fk_') !== false && !in_array(intval(str_replace('fk_', '', $id)), array_keys($aFormFields))) {
				$this->addErrLog(__('Incorrect field ID %s (Line %s)', $id, $this->errLine));
			}
		}

		$aBrands = array_keys($this->Brand->getOptions());
		$aCategories = array_keys($this->Category->getOptions());
		$aSubcategories = array_keys($this->Subcategory->getOptions());

		foreach($aData['data'] as $row) {
			$this->errLine++;

			// Проверить обязательные поля
			if ( !(isset($row['title']) && trim($row['title'])) ) {
				$this->addErrLog(__('Field `title` cannot be blank (Line %s)', $this->errLine));
			}
			if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
				$this->addErrLog(__('Field `title_rus` cannot be blank (Line %s)', $this->errLine));
			}
			if ( !(isset($row['code']) && trim($row['code'])) ) {
				$this->addErrLog(__('Field `code` cannot be blank (Line %s)', $this->errLine));
			}

			// Проверить необязательные поля
			if (isset($row['brand_id']) && !in_array($row['brand_id'], $aBrands)) {
				$this->addErrLog(__('Incorrect brand ID (Line %s)', $this->errLine));
			}
			if (isset($row['cat_id']) && !in_array($row['cat_id'], $aCategories)) {
				$this->addErrLog(__('Incorrect category ID (Line %s)', $this->errLine));
			}
			if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $aSubcategories)) {
				$this->addErrLog(__('Incorrect subcategory ID (Line %s)', $this->errLine));
			}

		}
		return $this->errLog;
	}

	protected function _createProducts($aData) {
		App::uses('Translit', 'Article.Vendor');
		
		// $aFormFields = $this->FormField->find('list', array('fields' => array('id', 'key')));
		$aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		foreach($aData['keys'] as $id) {
			if (strpos($id, 'fk_') !== false && !in_array(intval(str_replace('fk_', '', $id)), array_keys($aFormFields))) {
				throw new Exception(__('Incorrect field ID %s', $id));
			}
		}
		
		$aBrands = array_keys($this->Brand->getOptions());
		$aCategories = array_keys($this->Category->getOptions());
		$aSubcategories = array_keys($this->Subcategory->getOptions());
		
		$aID = array();
		$this->errLine = 1;
		foreach($aData['data'] as $row) {
			$this->errLine++;
			
			// Проверить обязательные поля
			if ( !(isset($row['title']) && trim($row['title'])) ) {
				throw new Exception('Field `title` cannot be blank (Line %s)');
			}
			if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
				throw new Exception('Field `title_rus` cannot be blank (Line %s)');
			}
			if ( !(isset($row['code']) && trim($row['code'])) ) {
				throw new Exception('Field `code` cannot be blank (Line %s)');
			}
			
			// Проверить необязательные поля
			if (isset($row['brand_id']) && !in_array($row['brand_id'], $aBrands)) {
				throw new Exception('Incorrect brand ID (Line %s)');
			}
			if (isset($row['cat_id']) && !in_array($row['cat_id'], $aCategories)) {
				throw new Exception('Incorrect category ID (Line %s)');
			}
			if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $aSubcategories)) {
				throw new Exception('Incorrect subcategory ID (Line %s)');
			}
			
			$row['object_type'] = 'Product';
			if (!isset($row['slug'])) {
				if (isset($row['title_rus']) && $row['detail_num']) {
					$row['slug'] = Translit::convert(trim($row['title_rus']).'-'.trim($row['code']), true);
					$row['slug'] = preg_replace('![^'.preg_quote('-').'a-z0-9_\s]+!', '', $row['slug']);
				}
			}
			if (!isset($row['published'])) {
				$row['published'] = 1;
			}
			if (!isset($row['active'])) {
				$row['active'] = 1;
			}
			if (!isset($row['show_detailnum'])) {
				$row['show_detailnum'] = 1;
			}

			$this->Product->clear();
			$data = array('Product' => $row);
			if (!$this->Product->save($data)) {
				throw new Exception('Cannot create product (Line %s)');
			}

			$formData = array('object_type' => 'ProductParam', 'object_id' => $this->Product->id);
			foreach ($row as $id => $val) {
				if (strpos($id, 'fk_') !== false) {
					$formData[$id] = $row[$id];
				}
			}

			$this->PMFormData->clear();
			if (!$this->PMFormData->save($formData)) {
				throw new Exception('Cannot save parameters (Line %s)');
			}

			$this->PMFormData->recalcFormula($this->PMFormData->id, $aFormFields);
			$data = array();
			// Создать SEO блок для продукта
			if (isset($row['title_rus']) && $row['code']) {
				$data['title'] = $row['title_rus'] . ' ' . $row['code'];
			}
			if (isset($row['title']) && $row['detail_num']) {
				$data['keywords'] = $row['title'] . ' ' . $row['detail_num'];
				$data['descr'] = $data['keywords'];
			}
			if ($data) {
				$data['object_type'] = 'Product';
				$data['object_id'] = $this->Product->id;

				$this->Seo->clear();
				$this->Seo->save($data);
			}

			$aID[] = $this->Product->id;
		}
		return $aID;
	}
    
	public function uploadNewProducts() {
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name'] ) {
				set_time_limit(60 * 30);
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				$errLog = $this->_checkCreatedProducts($aData);
				if ($errLog) {
					$this->set('errLog', $errLog);
				} else {
					$this->Product->getDataSource()->begin();
					$aID = $this->_createProducts($aData);
					$this->Product->getDataSource()->commit();

					$this->setFlash(__('%s products have been successfully uploaded', count($aID)), 'success');
					if (count($aID) > 100) {
						$file = Configure::read('tmp_dir').'user_products_'.$this->Auth->user('id').'.tmp';
						file_put_contents($file, implode("\r\n", $aID));
						$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.id' => 'list'));
					} else {
						$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.id' => implode(',', $aID)));
					}
				}
			}
		} catch (Exception $e) {
			$this->Product->getDataSource()->rollback();
			$this->setFlash(__($e->getMessage(), $this->errLine), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'uploadNewProducts'));
		}
	}
	
	public function checkProducts() {
		$keyField = 'code';
		$aCodes = array();
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name'] ) {
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				
				if (in_array('detail_num', $aData['keys'])) {
					$keyField = 'detail_num';
				}
				
				if (!Hash::get($aData, 'data.0.'.$keyField)) {
					throw new Exception(__('CSV file must contain `%s` field', $keyField));
				}
				$aCodes = Hash::extract($aData, 'data.{n}.'.$keyField);
			} else {
				$keyField = $this->request->data('keyField');
				if ($codes = $this->request->data('codes')) {
					$aCodes = explode(',', $codes);
				}
			}
			
			if ($aCodes) {
				$fields = array('Product.id', 'Product.code', 'Product.detail_num', 'Product.title', 'Product.title_rus');
				$countRecs = 0;
				if ($keyField == 'detail_num') {
					$conditions = array();
					foreach($aCodes as $number) {
						$conditions['OR'][] = array('Product.detail_num LIKE ' => '%'.trim($number).'%');
					}
					$order = 'Product.detail_num';
					$aData = $this->Product->find('all', compact('fields', 'conditions', 'order'));
					$countRecs = count($aData);
					$aProducts = array();
					foreach($aData as $product) {
						foreach(explode(' ', $product['Product']['detail_num']) as $_detail_num) {
							$aProducts[$_detail_num][] = $product; // встречаются разные детали с одинаковыми номерами!!!
						}
					}
				} else {
					$conditions = array('Product.code' => $aCodes);
					$order = 'Product.code';
					$aProducts = $this->Product->find('all', compact('fields', 'conditions', 'order'));
					$countRecs = count($aProducts);
					$aProducts = Hash::combine($aProducts, '{n}.Product.code', '{n}');
				}
				
				$this->set('keyField', $keyField);
				$this->set('aCodes', $aCodes);
				$this->set('aProducts', $aProducts);
				if ($this->request->data('print')) {
					$this->layout = 'print_xls';
					$this->render('check_products_print');
				} else {
					$msg = __('Found %s products / %s codes', $countRecs, count($aCodes));
					$this->setFlash($msg, 'success');
				}
			}
		} catch (Exception $e) {
			$this->setFlash(__($e->getMessage(), $this->errLine), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'checkProducts'));
		}
	}
	
	public function processBigCsv() {
		set_time_limit(60 * 10);
		$this->autoRender = false;
		$chunkSize = 20000;
		
		try {
			
			$aBrands = array_keys($this->Brand->getOptions());
			$aCategories = array_keys($this->Category->getOptions());
			$aSubcategories = array_keys($this->Subcategory->getOptions());
		
			$aData = $this->_parseCsv('big_csv.csv');
			$this->errLine = 1;
			$chunk = '';
			$chunkCount = 0;
			$chunkFile = 0;
			foreach($aData['data'] as $row) {
				$this->errLine++;
				
				// Проверить обязательные поля
				if ( !(isset($row['title']) && trim($row['title'])) ) {
					throw new Exception('Field `title` cannot be blank (Line %s)');
				}
				if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
					throw new Exception('Field `title_rus` cannot be blank (Line %s)');
				}
				if ( !(isset($row['code']) && trim($row['code'])) ) {
					throw new Exception('Field `code` cannot be blank (Line %s)');
				}
				
				// Проверить необязательные поля
				if (isset($row['brand_id']) && !in_array($row['brand_id'], $aBrands)) {
					throw new Exception('Incorrect brand ID (Line %s)');
				}
				if (isset($row['cat_id']) && !in_array($row['cat_id'], $aCategories)) {
					throw new Exception('Incorrect category ID (Line %s)');
				}
				if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $aSubcategories)) {
					throw new Exception('Incorrect subcategory ID (Line %s)');
				}
				$chunkCount++;
				$chunk.= implode(self::CSV_DIV, array_values($row))."\r\n";
				if ($chunkCount >= $chunkSize) {
					$chunkFile++;
					$f = 'big_csv_'.$chunkFile.'.csv';
					@unlink($f);
					fdebug(implode(self::CSV_DIV, $aData['keys'])."\r\n".$chunk, $f);
					$chunk = '';
					$chunkCount = 0;
				}
			}
			// last part of CSV
			$chunkFile++;
			$f = 'big_csv_'.$chunkFile.'.csv';
			@unlink($f);
			fdebug(implode(self::CSV_DIV, $aData['keys'])."\r\n".$chunk, $f);
			
			echo 'No errors in file';
		} catch (Exception $e) {
			echo 'Error!';
			echo __($e->getMessage(), $this->errLine);
		}
	}
	
}

