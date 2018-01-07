<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::uses('Product', 'Model');
App::uses('Logger', 'Model');
App::uses('PMFormData', 'Form.Model');
class Import1CTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Logger', 'ImportLog');

    public function execute() {
        $this->Logger->init(Configure::read('import.log'));

        $data = CsvReader::parse($this->params['csv_file']);

        if (!(isset($data['keys']) && $data['keys'])) {
            throw new Exception(__('Incorrect CSV headers'));
        }

        $paramA1 = 'fk_'.Configure::read('Params.A1');
        $paramA2 = 'fk_'.Configure::read('Params.A2');
        if (!($data['keys'][0] == 'code' && ($data['keys'][1] == $paramA1 || $data['keys'][1] == $paramA2))) {
            throw new Exception(__('Incorrect header keys: %s', print_r($data['keys'], true)));
        }

        if (!(isset($data['data']) && $data['data'])) {
            throw new Exception(__('Incorrect CSV data'));
        }

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
        $status = $this->Task->getStatus($this->id);
        if ($status !== Task::ABORT) { // могли прервать таск до его начала - проверяем, иначе перетрем событие ABORT
            $this->Task->setStatus($this->id, Task::RUN);
        }

        $this->Logger->write('PROCESS', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));

        $aID = array();
        $i = 0;
        $this->Product->unbindModel(array(
            'belongsTo' => array('Category', 'Subcategory', 'Brand'),
            'hasOne' => array('Media', 'Seo', 'Search', 'PMFormData')
        ), false);
        try {
            $this->PMFormData->trxBegin();

            $this->PMFormData->updateAll(array($data['keys'][1] => 0), true); // чистим остальные остатки

            foreach ($aData as $code => $val) {
                $i++;
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    throw new Exception(__('Processing was aborted by user'));
                }

                $fields = array('Product.id');
                $product = $this->Product->findByCode($code, $fields);
                $key = $data['keys'][1];
                $logData = array(
                    'code' => $code,
                    'fk_n' => $data['keys'][1],
                    'val' => $val,
                    'data' => implode(';', $data['data'][$i - 1]),
                    'status' => 'ERROR'
                );
                if ($product) {
                    $fields = array('PMFormData.id');
                    $formData = $this->PMFormData->findByObjectTypeAndObjectId('ProductParam', $product['Product']['id'], $fields);
                    $this->PMFormData->save(array('id' => $formData['PMFormData']['id'], $key => $val));

                    $logData['product_id'] = $product['Product']['id'];
                    $logData['form_data_id'] = $formData['PMFormData']['id'];
                    $logData['status'] = 'OK';

                    $aID[] = $product['Product']['id'];
                }

                if (Configure::read('import.db_log')) {
                    $this->ImportLog->clear();
                    $this->ImportLog->save($logData);
                }

                $this->Task->setProgress($this->id, $i);
            }
            $this->PMFormData->trxCommit();

            $this->Task->setData($this->id, 'xdata', $aID);
            $this->Task->setStatus($this->id, Task::DONE);
            $this->Logger->write('DONE', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));
            $this->Task->close($this->id); // сразу закрываем таск
        } catch (Exception $e) {
            $this->PMFormData->trxRollback();
            $this->Logger->write('ABORTED', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));
            throw $e;
        }
    }
}
