<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');

class UpdateProductsTask extends AppShell
{
    public $uses = array('Product', 'Form.PMFormField', 'Form.PMFormData', 'FormPrice');

    public function execute()
    {
        $conditions = array(); // 'object_id <' => 2000
        $total = $this->PMFormData->find('count', compact('conditions'));
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        $page = 1;
        $limit = 1000;
        $fields = $this->PMFormField->getFieldsList('SubcategoryParam', '');
        $i = 0;

        $aFields = array();
        foreach ($fields as $field) {
            $field = $field['PMFormField'];
            if ($field['field_type'] == FieldTypes::PRICE && !$field['price_formula']) {
                $aFields[] = $field;
            }
        }

        $this->PMFormData->trxBegin();
        while ($rowset = $this->PMFormData->find('all', compact('page', 'limit', 'conditions'))) {
            $page++;
            foreach ($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    $this->PMFormData->trxCommit(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $this->process($row['PMFormData'], $aFields);

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
        }
        $this->PMFormData->trxCommit();

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    private function process($row, $aFields)
    {
        $prices = $this->FormPrice->getProductPrices($row['object_id']);
        foreach ($aFields as $field) {
            $fk_id = $field['id'];
            if (!isset($prices[$fk_id]) && floatval($row['fk_' . $fk_id])) {
                $data = array('product_id' => $row['object_id'], 'fk_id' => $fk_id, 'price' => floatval($row['fk_' . $fk_id]), 'kurs' => 1, 'currency_from' => $field['price_currency'], 'koeff' => 1);
                $this->FormPrice->clear();
                $this->FormPrice->save($data);
            }
        }
    }
}
