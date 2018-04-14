<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');
App::uses('CsvReader', 'Vendor');
class CrossCsvParserTask extends AppShell {
    public $uses = array('Product');

    public function execute() {
        $this->Task->setProgress($this->id, 0, 3); // 2 subtasks
        $this->Task->setStatus($this->id, Task::RUN);

        $this->params['csv_file'] = ROOT.DS.APP_DIR.DS.'csv_codes.csv';

        $aData = $this->_readCsv($this->params['csv_file']); // subtask 1
        $aData = $this->_process($aData); // subtask 2

        try {
            $this->Product->getDataSource()->begin();
            $aID = $this->_updateProducts($aData); // subtask 3
            $this->Product->getDataSource()->commit();
        } catch (Exception $e) {
            $this->Product->trxRollback();
            throw new Exception($e->getMessage());
        }

        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    private function _readCsv($file) {
        $subtask_id = $this->Task->add(0, 'CrossCsvParser_readCsv', null, $this->id);
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

    private function _process($aData) {
        $subtask_id = $this->Task->add(0, 'CrossCsvParser_processData', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aData['data']));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $aRows = array();
        foreach($aData['data'] as $line => $row) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            $code = trim($row['code']);
            if ($code) {
                if (!isset($aRows[$code])) {
                    $aRows[$code] = array();
                }
                $aRows[$code][] = $row['fk_60'];
            }

            $this->Task->setProgress($subtask_id, $line + 1);
            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
        return $aRows;
    }

    private function _updateProducts($aRows) {
        $subtask_id = $this->Task->add(0, 'CrossCsvParser_updateProducts', null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, count($aRows));
        $this->Task->setStatus($subtask_id, Task::RUN);
        $progress = $this->Task->getProgressInfo($this->id);

        $i = 0;
        $aID = array();
        foreach($aRows as $code => $nums) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            $product = $this->Product->findByCode($code);
            if ($product) {
                $crossNums = trim($product['PMFormData']['fk_60']);
                if ($crossNums) {
                    $crossNums = implode(', ', $nums).",\r\n".$crossNums;
                } else {
                    $crossNums = implode(', ', $nums);
                }
                $aID[] = $product['Product']['id'];
                $product['PMFormData']['fk_60'] = $crossNums;

                unset($product['Media']);
                unset($product['Seo']);
                unset($product['Search']);
                $this->Product->saveAll($product);
            }

            $i++;
            $this->Task->setProgress($subtask_id, $i);
            $_progress = $this->Task->getProgressInfo($subtask_id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);
        }

        $this->Task->setStatus($subtask_id, Task::DONE);
        $this->Task->setProgress($this->id, $progress['progress'] + 1);
        $this->Task->saveStatus($this->id);
        return $aID;
    }
}
