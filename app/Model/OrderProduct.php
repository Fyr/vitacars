<?php
App::uses('AppModel', 'Model');
class OrderProduct extends AppModel {

    public $belongsTo = array(
        'Product' => array(
            'foreignKey' => 'product_id'
        ),
    );

    public $hasOne = array(
        'Media' => array(
            'className' => 'Media.Media',
            'foreignKey' => 'object_id',
            'conditions' => array('Media.media_type' => 'image', 'Media.object_type' => 'Product', 'Media.main_by' => 1),
            'dependent' => true
        )/*,
        'PMFormData' => array(
            'className' => 'Form.PMFormData',
            // 'foreignKey' => 'product_id',
            'conditions' => array('PMFormData.object_type' => 'ProductParam'),
            'dependent' => true
        )*/
    );
}
