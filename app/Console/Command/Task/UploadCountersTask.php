<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::uses('CsvWriter', 'Vendor');

class UploadCountersTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'DetailNum', 'ProductRemain');

    const ERR_REPORT_FNAME = 'upload-counters-err-report.csv';
    private $keyField, $aData, $errReport = array(), $uploadLine = array();

    public function execute() {
        $subtasks = $this->params['set_zero'] ? 4 : 3; // 4 subtasks
        $this->Task->setProgress($this->id, 0, $subtasks); 
        $this->Task->setStatus($this->id, Task::RUN);

        // Предварительная проверка на права доступа к полям
        $aData['keys'] = CsvReader::getHeaders($this->params['csv_file']);
        // $keyField = (in_array('detail_num', $aData['keys'])) ? 'detail_num' : 'code';
        $this->keyField = $aData['keys'][0];
        if (!($this->keyField === 'detail_num' || $this->keyField === 'code')) {
            throw new Exception(__('First field in CSV file must be `detail_num` or `code`'));
        }

        $aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
        $fieldRights = $this->params['fieldRights'];
        $this->checkFieldRights($aData['keys'], array_keys($aFormFields), $fieldRights);

        $this->aData = $this->_readCsv($this->params['csv_file']); // subtask 1

        $this->Product->unbindModel(array(
            'belongsTo' => array('Category', 'Subcategory', 'Brand'),
            'hasOne' => array('Seo', 'Media', 'Search')
        ), false);
        $aCounters = $this->_getCounters($this->aData['data']); // subtask 2

        try {
            $this->Product->trxBegin();
            $aID = $this->_updateParams($this->aData['keys'], $aCounters); // subtask 3-4
            $this->Product->trxCommit();
        } catch (Exception $e) {
            $this->Product->trxRollback();
            throw new Exception($e->getMessage());
        }

        $this->Task->setData($this->id, 'xdata', array(
            'product_ids' => $aID, 
            'total' => count($this->aData['data']), 
            'errors' => count($this->errReport),
            'error_report' => self::ERR_REPORT_FNAME
        ));
        $this->Task->setProgress($this->id, $subtasks);
        $this->Task->setStatus($this->id, Task::DONE);

        if ($this->errReport) {
            $this->writeErrCsvReport();
        }
    }

    public function cleanup() {
        unlink($this->params['csv_file']);
    }

    private function _readCsv($file) {
        $subtask_id = $this->Task->add(0, 'UploadCounters_readCsv', null, $this->id);
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

    private function addErrReport($line, $msg) {
        if (!isset($this->errReport[$line])) {
            $this->errReport[$line] = array();
        }
        $this->errReport[$line][] = $msg;
    }

    private function writeErrCsvReport() {
        $keys = $this->aData['keys'];
        $keys[] = 'status';
        $csvWriter = new CsvWriter(WWW_ROOT.DS.self::ERR_REPORT_FNAME, $keys);
        $csvWriter->writeHeaders();
        foreach($this->errReport as $line => $aErrors) {
            $row = $this->aData['data'][$line];
            $row['status'] = implode("\r\n", $aErrors);
            $csvWriter->writeData($row);
        }
    }

    /**
     * Проинициализировать счетчики в зав-ти от ID продукта
     *
     * @param array $aData
     */
    private function _getCounters($aData) {
        $subtask_id = $this->Task->add(0, 'UploadCounters_initCounters', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aData));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $aParams = array();
        foreach($aData as $line => $row) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            list($number) = array_values($row);
            $ids = array();
            if ($this->keyField == 'detail_num') {
                $conditions = array('detail_num' => $this->DetailNum->strip($number), 'num_type' => DetailNum::ORIG);
                $ids = $this->DetailNum->find('all', compact('conditions'));
                $ids = Hash::extract($ids, '{n}.DetailNum.product_id');
                $ids = array_unique($ids);
            } elseif ($this->keyField == 'code') {
                $fields = array('Product.id');
                $conditions = array('Product.code' => $number);
                $ids = $this->Product->find('all', compact('fields', 'conditions'));
                $ids = Hash::extract($ids, '{n}.Product.id');
            }
            if ($ids) {
                array_shift($row); // исключить 1й ключ из обрабатываемой строки (номер детали)
                foreach($ids as $object_id) {
                    if (!isset($aParams[$object_id])) {
                        $aParams[$object_id] = array();
                    }
                    if (!isset($this->uploadLine[$object_id])) {
                        $this->uploadLine[$object_id] = $line;
                    }
                    foreach($row as $counter => $count) {
                        if (isset($aParams[$object_id][$counter])) {
                            $aParams[$object_id][$counter]+= floatval($count);
                        } else {
                            $aParams[$object_id][$counter] = floatval($count);
                        }
                    }
                }
            } else {
                $this->addErrReport(
                    $line,
                    __('Cannot find product for counter by `%s`=`%s`  (Line %s)', array($this->keyField, $number, $line + 2))
                );
            }

            $this->Task->setProgress($subtask_id, $line + 1);

            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);

        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
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
        foreach($keys as $id) {
            $aKeys[$id] = 0;
        }
        // Считать константы для вычисления формул
        $this->loadModel('Form.PMFormConst');
        $fields = array('key', 'value');
        $conditions = array('PMFormConst.object_type' => 'SubcategoryParam');
        $aConst = $this->PMFormConst->find('list', compact('fields', 'conditions'));

        $aID = array();

        $a1 = 'fk_'.Configure::read('Params.A1');
        $a2 = 'fk_'.Configure::read('Params.A2');

        $subtask_id = $this->Task->add(0, 'UploadCounters_updateCounters', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aParams));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $i = 0;
        foreach($aParams as $object_id => $counters) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            $product = $this->Product->findById($object_id);
            if ($product) { // product found
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
                    // throw new Exception(__('Product params could not be saved: %s', print_r($counters, true)));
                    $line = $this->uploadLine[$object_id];
                    $this->addErrReport(
                        $line,
                        __('DB error! Cannot save counters for product with ID=`%s` (Line %s)', array($object_id, $line + 2))
                    );
                }
                if ($remain) {
                    $product_id = $object_id;
                    $this->ProductRemain->clear();
                    $this->ProductRemain->save(compact('product_id', 'remain'));
    
                    // скорректировать статистику за год
                    $field = 'fk_'.Configure::read(($remain > 0) ? 'Params.incomeY' : 'Params.outcomeY');
                    $this->PMFormData->saveField($field, intval($this->PMFormData->field($field)) + $remain); // уже выставлен нужный $this->PMFormData->id
                }

                $aID[] = $object_id;                
            } else {
                // it seems that we have inconsistency in DB - we have search by detail_num or code and found product with non-existed ID
                $line = $this->uploadLine[$object_id];
                $this->addErrReport(
                    $line,
                    __('Inconsistency DB error! Cannot find product for counter by ID=`%s` (Line %s)', array($object_id, $line + 2))
                );
            }

            $i++;
            $this->Task->setProgress($subtask_id, $i);

            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);

        if (!$this->params['set_zero']) {
            return $aID;
        }

        $outcomeY = 'fk_'.Configure::read('Params.outcomeY');

        $conditions = array('object_type' => 'ProductParam', 'NOT' => array('object_id' => array_keys($aParams), 'AND' => $aKeys));
        $total = $this->PMFormData->find('count', compact('conditions'));

        $subtask_id = $this->Task->add(0, 'UploadCounters_updateRest', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, $total);
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $page = 1;
        $limit = 1000;
        $order = array('object_id');
        $i = 0;
        while ($rows = $this->PMFormData->find('all', compact('conditions', 'page', 'limit', 'order'))) { // получаем записи порциями по 1000
            $page++;
            $remain = 0;
            foreach($rows as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    $this->Task->setStatus($subtask_id, Task::ABORTED);
                    throw new Exception(__('Processing was aborted by user'));
                }

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

                $i++;
                $this->Task->setProgress($subtask_id, $i + 1);

                $_progress = $this->Task->getProgressInfo($subtask_id);
                $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
            }
        }
        $this->Task->setStatus($subtask_id, Task::DONE);

        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);

        return $aID;
    }
}
