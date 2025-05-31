<?php
App::uses('AppModel', 'Model');
App::uses('Product', 'Model');
class SiteOrderDetails extends AppModel {
    public $belongsTo = array(
        'Product'
    );
}
