<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::uses('Product', 'Model');
App::uses('ProductRemain', 'Model');
App::uses('Logger', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('PMFormField', 'Form.Model');
class Import1CTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Logger', 'ImportLog', 'ProductRemain', 'Form.PMFormField');

    const TASK_NAME = 'Import1C';

    private $_stockFks = array();

    public function execute() {
        $this->Logger->init(Configure::read('import.log'));

        $data = CsvReader::parse($this->params['csv_file']);

        $this->_checkCSV($data);

        $paramA1 = 'fk_'.Configure::read('Params.A1');
        $paramA2 = 'fk_'.Configure::read('Params.A2');

        // подсуммировать остатки по одинаковым кодам
        $aData = array();
        foreach($data['data'] as $i => $_data) {
            $key = $data['keys'][1];
            if (!isset($aData[$_data['code']])) {
                $aData[$_data['code']] = 0;
            }
            $aData[$_data['code']]+= $_data[$key];
        }

        $this->Task->setProgress($this->id, 0, count($aData));
        $this->Task->setStatus($this->id, Task::RUN);
        $this->Logger->write('PROCESS', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));

        $aID = array();
        $i = 0;
        $this->Product->unbindModel(array(
            'belongsTo' => array('Category', 'Subcategory', 'Brand'),
            'hasOne' => array('Media', 'Seo', 'Search', 'PMFormData')
        ), false);
        try {
            $this->PMFormData->trxBegin();

            // $this->PMFormData->updateAll(array($data['keys'][1] => 0), true); // отстатки можно почистать с самого начала, но это занимает больше времени

            foreach ($aData as $code => $val) {
                $i++;
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    throw new Exception(__('Processing was aborted by user'));
                }

                $fields = array('Product.id');
                $product = $this->Product->findByCodeAndIsFake($code, 0, $fields);
                $key = $data['keys'][1];
                $logData = array(
                    'code' => $code,
                    'fk_n' => $data['keys'][1],
                    'val' => $val,
                    'data' => implode(';', $data['data'][$i - 1]),
                    'status' => 'ERROR'
                );
                if ($product) {
                    $product_id = $product['Product']['id'];
                    $fields = array('PMFormData.id', $paramA1, $paramA2);
                    $formData = $this->PMFormData->findByObjectTypeAndObjectId('ProductParam', $product_id, $fields);
                    $this->PMFormData->save(array('id' => $formData['PMFormData']['id'], $key => $val));

                    $logData = array();
                    $aID[] = $product['Product']['id'];

                    // для отчета по продажам
                    $a1_val = intval($formData['PMFormData'][$paramA1]);
                    $a2_val = intval($formData['PMFormData'][$paramA2]);
                    $remain = 0;
                    if ($data['keys'][1] == $paramA1) {
                        $remain = intval($val) - $a1_val;
                    } elseif ($data['keys'][1] == $paramA2) {
                        $remain = intval($val) - $a2_val;
                    }
                    if ($remain) {
                        $this->ProductRemain->clear();
                        $this->ProductRemain->save(compact('product_id', 'remain'));
                    }
                }

                if (Configure::read('import.db_log') && $logData) {
                    $this->ImportLog->clear();
                    $this->ImportLog->save($logData);
                }

                $this->Task->setProgress($this->id, $i);
            }

            $this->PMFormData->updateAll(array($data['keys'][1] => 0), array('object_type' => 'ProductParam', 'NOT' => array('object_id' => $aID))); // чистим остальные остатки
            $this->PMFormData->trxCommit();

            // если был обработан файл _1, пытаемся обработать след.файл _2
            // (След.файл - должен быть другого типа в первую очередь)
            $type = intval($this->_getCsvType($this->params['csv_file']));
            $task = false;
            for ($n = 0; $n <= count($this->_getStockFks()); $n++) {
                $type++;
                if ($type > count($this->_getStockFks())) {
                    $type = 1;
                }
                $task = $this->_getNextTask($type . '');
                if ($task) {
                    break;
                }
            }

            if ($task) {
                // чтобы какой-то процесс не встрял между запуском текущего таска и следующим - сразу меняем найденному таску статус на RUN
                $this->Task->setStatus($task['Task']['id'], Task::RUN);
            }

            $this->Logger->write('DONE', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));
            $this->Task->setData($this->id, 'xdata', $aID);
            $this->Task->setStatus($this->id, Task::DONE);
            $this->Task->close($this->id);

            if ($task) {
                $this->Task->runBkg($task['Task']['id']);
            }
        } catch (Exception $e) {
            $this->PMFormData->trxRollback();
            $this->Logger->write('ABORTED', array('TaskID' => $this->id, 'File' => $this->params['csv_file'], 'Error' => $e->getMessage()));
            throw $e; // throw exception for BkgService
        }
    }

    private function _checkCSV($data)
    {
        if (!(isset($data['keys']) && $data['keys'])) {
            throw new Exception(__('Incorrect CSV headers'));
        }

        if (!(isset($data['data']) && $data['data'])) {
            throw new Exception(__('Incorrect CSV data'));
        }

        if (!($data['keys'][0] == 'code' && in_array($data['keys'][1], $this->_getStockFks()))) {
            throw new Exception(__('Incorrect header keys: %s', print_r($data['keys'], true)));
        }
    }

    private function _getStockFks()
    {
        if ($this->_stockFks) {
            return $this->_stockFks;
        }
        $conditions = array('object_type' => 'SubcategoryParam', 'is_stock' => 1);
        $stockFields = $this->PMFormField->find('all', compact('conditions'));
        foreach (Hash::extract($stockFields, '{n}.PMFormField.id') as $fk_id) {
            $this->_stockFks[] = 'fk_' . $fk_id;
        }
        return $this->_stockFks;
    }

    private function _getCsvType($fname)
    {
        return array_pop(explode('_', basename($fname, '.csv')));
    }

    private function _getNextTask($type)
    {
        $conditions = array('task_name' => self::TASK_NAME, 'status' => array(Task::CREATED), 'params LIKE ' => '%_' . $type . '.csv%');
        $order = array('Task.id' => 'DESC');
        $tasks = $this->Task->find('all', compact('conditions', 'order'));
        if ($tasks) {
            // извлекаем последний созданный таск
            $lastTask = array_shift($tasks);

            // остальные таски - удаляем
            foreach ($tasks as $task) {
                $this->Logger->write('DELETE', am(array('TaskID' => $task['Task']['id']), unserialize($task['Task']['params'])));
                $this->Task->remove($task['Task']['id']);
            }
            return $lastTask;
        }
        return false;
    }
}
