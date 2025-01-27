<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');

class UploadNewProductsTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'DetailNum', 'Brand', 'Category', 'Subcategory');

    private $aFormFields, $aBrands, $aCategories, $aSubcategories;

    public function execute() {
        $this->Task->setProgress($this->id, 0, 3); // 3 subtasks
        $this->Task->setStatus($this->id, Task::RUN);

        $aData['keys'] = CsvReader::getHeaders($this->params['csv_file']);

        $this->aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
        $fieldRights = $this->params['fieldRights'];
        $this->checkFieldRights($aData['keys'], array_keys($this->aFormFields), $fieldRights);

        $this->aBrands = array_keys($this->Brand->getOptions());
        $this->aCategories = array_keys($this->Category->getOptions());
        $this->aSubcategories = array_keys($this->Subcategory->getOptions());

        $aData = $this->_readCsv($this->params['csv_file']); // subtask 1

        // file_put_contents('csv.data', serialize($aData));

        $this->_checkCreatedProducts($aData); // subtask 2
        /*
        if ($aErrLog) {
            $this->Task->setData($this->id, 'xdata', compact('aErrLog'));
            $this->Task->setStatus($this->id, Task::DONE);
            return;
        }
        */

        try {
            $this->Product->getDataSource()->begin();
            $aID = $this->_createProducts($aData); // subtask 3
            $this->Product->getDataSource()->commit();
        } catch (Exception $e) {
            $this->Product->trxRollback();
            throw new Exception($e->getMessage());
        }

        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    public function cleanup() {
        unlink($this->params['csv_file']);
    }

    private function _readCsv($file) {
        $subtask_id = $this->Task->add(0, 'UploadNewProducts_readCsv', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $progress = $this->Task->getProgressInfo($this->id);

        $aData = CsvReader::parse($this->params['csv_file'], array(
            'Task' => $this->Task,
            'task_id' => $this->id,
            'subtask_id' => $subtask_id
        ));
        // $aData = unserialize(file_get_contents('csv.data');

        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
        return $aData;
    }

    /**
     * Проверить валидность данных для продуктов (чтоб можно было исправиьт сразу все ошибки - errLog)
     *
     * @param array $errlog
     */
    private function _checkCreatedProducts($aData) {
        $subtask_id = $this->Task->add(0, 'UploadCounters_checkProducts', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aData['data']));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        foreach($aData['data'] as $line => $row) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            // Проверить обязательные поля
            if ( !(isset($row['title']) && trim($row['title'])) ) {
                throw new Exception(__('Field `title` cannot be blank (Line %s)', $line + 2));
            }
            if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
                throw new Exception(__('Field `title_rus` cannot be blank (Line %s)', $line + 2));
            }
            if ( !(isset($row['code']) && trim($row['code'])) ) {
                throw new Exception(__('Field `code` cannot be blank (Line %s)', $line + 2));
            }

            // Проверить необязательные поля
            if (isset($row['brand_id']) && !in_array($row['brand_id'], $this->aBrands)) {
                throw new Exception(__('Incorrect brand ID (Line %s)', $line + 2));
            }
            if (isset($row['cat_id']) && !in_array($row['cat_id'], $this->aCategories)) {
                throw new Exception(__('Incorrect category ID (Line %s)', $line + 2));
            }
            if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $this->aSubcategories)) {
                throw new Exception(__('Incorrect subcategory ID (Line %s)', $line + 2));
            }

            $this->Task->setProgress($subtask_id, $line + 1);
            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
    }

    /**
     * Обновить счетчики по ID продукта
     *
     * @param array $aParams
     */
    private function _createProducts($aData) {
        App::uses('Translit', 'Article.Vendor');

        $subtask_id = $this->Task->add(0, 'UploadCounters_createProducts', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aData['data']));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $aConst = $this->PMFormConst->find('all');

        $aID = array();
        foreach($aData['data'] as $line => $row) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
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

            $formData = array('object_type' => 'ProductParam');
            foreach ($row as $id => $val) {
                if (strpos($id, 'fk_') !== false) {
                    $formData[$id] = $row[$id];
                }
            }
            
            $seo = array();
            // Создать SEO блок для продукта
            if (isset($row['title_rus']) && $row['code']) {
                $seo['title_by'] = $row['title_rus'].' '.$row['code'];
                $seo['title_ru'] = $row['code'].' '.$row['title_rus'];
            }
            if (isset($row['title']) && $row['code']) {
                $seo['title_ua'] = $row['code'].' '.$row['title'];
            }
            if (isset($row['title']) && $row['detail_num']) {
                $seo['keywords_by'] = $row['title_by'].' '.$row['detail_num'];
                $seo['keywords_ru'] = $row['detail_num'].' '.$row['title_ru'];
                $seo['keywords_ua'] = $row['detail_num'].' '.$row['title_ua'];
                $seo['descr_by'] = $seo['keywords_by'];
                $seo['descr_ru'] = $seo['keywords_ru'];
                $seo['descr_ua'] = $seo['keywords_ua'];
            }
            if ($seo) {
                $seo['object_type'] = 'Product';
            }

            $data = array(
                'Product' => $row,
                'PMFormData' => $formData,
                'Seo' => $seo
            );
            $this->Product->clear();
            
            if (!$this->Product->saveAll($data)) {
                throw new Exception('Cannot create product (Line %s)', $line + 2);
            }

            if ($this->params['recalc_formula']) {
                $this->PMFormData->recalcFormula($this->Product->PMFormData->id, $this->aFormFields, $aConst);
            }

            $aID[] = $this->Product->id;

            $this->Task->setProgress($subtask_id, $line + 1);
            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
        return $aID;
    }
}
