<?php
App::uses('AppModel', 'Model');

class FormPrice extends AppModel
{

    public function getProductPrices($product_id)
    {
        $rows = $this->find('all', array('conditions' => compact('product_id')));
        return Hash::combine($rows, '{n}.FormPrice.fk_id', '{n}.FormPrice');
    }
}
