<?php
App::uses('AppModel', 'Model');
App::uses('Client', 'Model');
App::uses('SiteOrderCompany', 'Model');
class SiteOrder extends AppModel {

    public $belongsTo = array(
        'Client' => array(
            'className' => 'Client',
            'foreignKey' => 'user_id',
            'dependent' => false
        ),
        'SiteOrderCompany' => array(
            'className' => 'SiteOrderCompany',
            'foreignKey' => 'user_id',
            'dependent' => false
        ),
    );

    // to have specific relations, we have to declare it as "hasOne" to have more control
    /*
    public $hasOne = array(
        'ClientCompany' => array(
            'className' => 'ClientCompany',
            'conditions' => array('SiteOrder.user_id' => '`ClientCompany`.user_id'),
            'foreignKey' => false,
            'dependent' => false
        ),
    );
    */

	public $validate = array(
		'username' => array(
			'checkNotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Field cannot be blank',
			),
			'checkNameLen' => array(
				'rule' => array('between', 3, 50),
				'message' => 'The name must be between 3 and 50 characters'
			),
		),
		'email' => array(
			'checkNotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Field cannot be blank',
			),
			'checkEmail' => array(
				'rule' => 'email',
				'message' => 'Email is incorrect'
			)
		),
		'phone' => array(
			'checkNotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Field cannot be blank'
			)
		),
		'address' => array(
			'checkNotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Field cannot be blank'
			)
		)
	);
}
