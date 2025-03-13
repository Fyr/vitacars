<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::uses('CsvWriter', 'Vendor');

class UploadNewProductsTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'DetailNum', 'Brand', 'Category', 'Subcategory');

    const ERR_REPORT_FNAME = 'upload-new-products-err-report.csv';
    private $aFormFields, $aBrands, $aCategories, $aSubcategories, $errReport = array();

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

        $this->_checkCreatedProducts($aData); // subtask 2

        try {
            $this->Product->getDataSource()->begin();
            $aID = $this->_createProducts($aData); // subtask 3
            $this->Product->getDataSource()->commit();
        } catch (Exception $e) {
            $this->Product->trxRollback();
            throw new Exception($e->getMessage());
        }

        $this->Task->setData($this->id, 'xdata', array(
            'product_ids' => $aID, 
            'total' => count($aData['data']), 
            'errors' => count($this->errReport),
            'error_report' => self::ERR_REPORT_FNAME
        ));
        $this->Task->setStatus($this->id, Task::DONE);

        if ($this->errReport) {
            $this->writeErrCsvReport($aData['keys']);
        }
    }

    public function cleanup() {
        unlink($this->params['csv_file']);
    }

    private function writeErrCsvReport($keys) {
        $keys[] = 'status';
        $csvWriter = new CsvWriter(WWW_ROOT.DS.self::ERR_REPORT_FNAME, $keys);
        $csvWriter->writeHeaders();
        foreach($this->errReport as $line => $row) {
            $row['status'] = implode("\r\n", $row['status']);
            $csvWriter->writeData($row);
        }
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

            $rowStatus = array();

            // Check mandatory fields
            if ( !(isset($row['title']) && trim($row['title'])) ) {
                $rowStatus[] = __('Field `title` cannot be blank (Line %s)', $line + 2);
            }
            if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
                $rowStatus[] = __('Field `title_rus` cannot be blank (Line %s)', $line + 2);
            }
            if ( !(isset($row['code']) && trim($row['code'])) ) {
                $rowStatus[] = __('Field `code` cannot be blank (Line %s)', $line + 2);
            }

            // Check non-mandatory fields
            if (isset($row['brand_id']) && !in_array($row['brand_id'], $this->aBrands)) {
                $rowStatus[] = __('Incorrect brand ID (Line %s)', $line + 2);
            }
            if (isset($row['cat_id']) && !in_array($row['cat_id'], $this->aCategories)) {
                $rowStatus[] = __('Incorrect category ID (Line %s)', $line + 2);
            }
            if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $this->aSubcategories)) {
                $rowStatus[] = __('Incorrect subcategory ID (Line %s)', $line + 2);
            }

            if ($rowStatus) {
                $row['status'] = $rowStatus;
                $this->errReport['line'.$line] = $row;
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

            $origRow = $row; // row is copied

            // skip already failed line
            if (!isset($this->errReport['line'.$line])) {
                $row['object_type'] = 'Product';
                if (!isset($row['slug'])) {
                    $row['slug'] = Translit::convert(trim($row['title_rus']).'-'.trim($row['code']), true);
                    $row['slug'] = preg_replace('![^'.preg_quote('-').'a-z0-9_\s]+!', '', $row['slug']);
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
                
                $detail_nums = (isset($row['detail_num'])) ? $row['detail_num'] : $row['code'];

                $seo = array('object_type' => 'Product');

                // Создать SEO блок для продукта
                $seo['title_by'] = $row['title_rus'].' '.$row['code'];
                $seo['title_ru'] = $row['code'].' '.$row['title_rus'];
                $seo['title_ua'] = $row['code'].' '.$row['title'];
                $seo['keywords_by'] = $seo['title_by'].' '.$detail_nums;
                $seo['keywords_ru'] = $detail_nums.' '.$seo['title_ru'];
                $seo['keywords_ua'] = $detail_nums.' '.$seo['title_ua'];
                $seo['descr_by'] = $seo['keywords_by'];
                $seo['descr_ru'] = $seo['keywords_ru'];
                $seo['descr_ua'] = $seo['keywords_ua'];

                $data = array(
                    'Product' => $row,
                    'PMFormData' => $formData,
                    'Seo' => $seo
                );
                $this->Product->clear();
                if ($this->Product->saveAll($data)) {
                    if ($this->params['recalc_formula']) {
                        $this->PMFormData->recalcFormula($this->Product->PMFormData->id, $this->aFormFields, $aConst);
                    }
        
                    $aID[] = $this->Product->id;
                } else {
                    $origRow['status'] = array(
                        __('Cannot create product (Line %s)', $line + 2)
                    );
                    $this->errReport['line'.$line] = $origRow;
                }
            }

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
