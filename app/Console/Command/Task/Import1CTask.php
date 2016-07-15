<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::uses('Product', 'Model');
App::uses('Logger', 'Model');
App::uses('PMFormData', 'Form.Model');
class Import1CTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Logger');

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

        $this->Task->setProgress($this->id, 0, count($data['data']));
        $status = $this->Task->getStatus($this->id);
        if ($status !== Task::ABORT) { // могли прервать таск до его начала - проверяем, иначе перетрем событие ABORT
            $this->Task->setStatus($this->id, Task::RUN);
        }

        $this->Logger->write('PROCESS', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));

        $aID = array();
        foreach($data['data'] as $i => $_data) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Logger->write('ABORTED', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));
                throw new Exception(__('Processing was aborted by user'));
            }

            $product = $this->Product->findByCode($_data['code']);
            $key = $data['keys'][1];
            $logData = array(
                'code' => $_data['code'],
                'fk_n' => $data['keys'][1],
                'val' => $_data[$key],
                'data' => implode(';', $_data),
                'status' => 'ERROR'
            );
            if ($product) {
                $this->PMFormData->trxBegin();
                $this->PMFormData->save(array('id' => $product['PMFormData']['id'], $key => $_data[$key]));
                $this->PMFormData->trxCommit();

                $logData['product_id'] = $product['Product']['id'];
                $logData['form_data_id'] = $product['PMFormData']['id'];
                $logData['status'] = 'OK';

                $aID[] = $product['Product']['id'];
            }

            if (Configure::read('import.db_log')) {
                $this->ImportLog->clear();
                $this->ImportLog->save($logData);
            }

            $this->Task->setProgress($this->id, $i + 1);
        }

        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);
        $this->Logger->write('DONE', array('TaskID' => $this->id, 'File' => $this->params['csv_file']));
    }
}
