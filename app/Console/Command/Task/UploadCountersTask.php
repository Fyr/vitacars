<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
class UploadCountersTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'DetailNum');

    public function execute() {
        $this->Task->setProgress($this->id, 0, $this->params['set_zero'] ? 3 : 2); // 3 subtasks
        $this->Task->setStatus($this->id, Task::RUN);

        $aData = CsvReader::parse($this->params['csv_file']);

        $fieldRights = $this->params['fieldRights'];
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

        try {
            $this->Product->trxBegin();
            $aID = $this->_updateParams($aData['keys'], $this->_getCounters($keyField, $aData['data']));
            $this->Product->trxCommit();
        } catch (Exception $e) {
            $this->Product->trxRollback();
            throw new Exception($e->getMessage());
        }

        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);
        unlink($this->params['csv_file']);
    }

    /**
     * Проинициализировать счетчики в зав-ти от ID продукта
     *
     * @param unknown_type $aData
     */
    private function _getCounters($keyField = 'detail_num', $aData) {
        $subtask_id = $this->Task->add(0, 'UploadCounters_initCounters', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aData));
        $this->Task->setStatus($subtask_id, Task::RUN);

        $aParams = array();
        foreach($aData as $i => $row) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            list($number) = array_values($row);
            $ids = array();
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
            $this->Task->setProgress($subtask_id, $i + 1);

            $_progress = $this->Task->getProgressInfo($subtask_id);
            $progress = $this->Task->getProgressInfo($this->id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);

        $this->Task->setProgress($this->id, 1);
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
        $aFormFields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
        foreach($keys as $id) {
            if (strpos($id, 'fk_') !== false && !in_array(intval(str_replace('fk_', '', $id)), array_keys($aFormFields))) {
                throw new Exception(__('Incorrect field ID %s', $id));
            }
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
        $i = 0;
        foreach($aParams as $object_id => $counters) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

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
            // $this->PMFormData->recalcFormula($this->PMFormData->id, $aFormFields);
            $aID[] = $object_id;

            $i++;
            $this->Task->setProgress($subtask_id, $i);

            $_progress = $this->Task->getProgressInfo($subtask_id);
            $progress = $this->Task->getProgressInfo($this->id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, 2);
        $this->Task->saveStatus($this->id);
        if (!$this->params['set_zero']) {
            return $aID;
        }

        $outcomeY = 'fk_'.Configure::read('Params.outcomeY');
        /*
        if (in_array($a1, $keys) || in_array($a2, $keys)) {
            $fields = array_merge(array('id', 'object_id', $outcomeY), $keys);
        }
        */

        $conditions = array('object_type' => 'ProductParam', 'NOT' => array('object_id' => array_keys($aParams), 'OR' => $aKeys));
        $total = $this->PMFormData->find('count', compact('conditions'));

        $subtask_id = $this->Task->add(0, 'UploadCounters_updateRest', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, $total);
        $this->Task->setStatus($subtask_id, Task::RUN);

        $page = 1;
        $limit = 1000;
        $order = array('object_id');
        $i = 0;
        while ($rows = $this->PMFormData->find('all', compact('conditions', 'page', 'limit', 'order'))) {
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
                //$this->PMFormData->_recalcFormula($row, $aFormFields, $aConst);
                // $aID[] = $row['PMFormData']['object_id'];

                $i++;
                $this->Task->setProgress($subtask_id, $i + 1);

                $_progress = $this->Task->getProgressInfo($subtask_id);
                $progress = $this->Task->getProgressInfo($this->id);
                $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
            }
        }
        $this->Task->setStatus($subtask_id, Task::DONE);

        $this->Task->setProgress($this->id, 3);
        $this->Task->saveStatus($this->id);

        return $aID;
    }
}
