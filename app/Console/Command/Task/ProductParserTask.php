<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');
App::uses('PMFormData', 'Form.Model');
class ProductParserTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField');

    public function execute() {
        $conditions = array('Brand.title' => array('DEUTZ', 'DEUTZ FAHR'));
        $total = $this->Product->find('count', compact('conditions'));
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        $page = 1;
        $limit = 1000;
        $ids = array();
        $i = 0;
        $fk_id = 'fk_'.Configure::read('Params.crossNumber');

        while ($rowset = $this->Product->find('all', compact('conditions', 'page', 'limit'))) {
            $page++;
            foreach($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    // $this->Product->trxRollback(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $lSave = false;
                if ($row['Brand']['title'] == 'DEUTZ') {
                    $var = 'PMFormData.'.$fk_id;
                    $crossNumber = Hash::get($row, 'PMFormData.'.$fk_id);
                    if (strpos($crossNumber, 'DEUTZ / KHD ') !== false) {
                        $crossNumber = trim(str_replace('DEUTZ / KHD ', '', $crossNumber));
                        $row['PMFormData'][$fk_id] = $crossNumber;
                        $lSave = true;
                    }
                } elseif ($row['Brand']['title'] == 'DEUTZ FAHR') {
                    $detail_nums = $row['Product']['detail_num'];
                    $code = $row['Product']['code'];
                    if (strpos($detail_nums, 'SDF') > 0) {
                        $detail_nums = str_replace('SDF', '', $detail_nums);
                        $detail_nums = array_unique(explode(', ', $detail_nums));
                        $row['Product']['detail_num'] = implode(', ', $detail_nums);
                        $lSave = true;
                    }
                    if (strpos($code, 'SDF') > 0) {
                        $code = str_replace('SDF', '', $code);
                        $row['Product']['code'] = $code;
                        $lSave = true;
                    }
                }

                if ($lSave) {
                    $ids[] = $row['Product']['id'];
                    $this->Product->trxBegin();
                    $this->Product->save($row);
                    $this->Product->trxCommit();
                }

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
        }


        $this->Task->setData($this->id, 'xdata', $ids);
        $this->Task->setStatus($this->id, Task::DONE);
    }
}
