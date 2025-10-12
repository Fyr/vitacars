<?
App::uses('AppModel', 'Model');
class Category extends AppModel {

    public $hasOne = array(
		'Seo' => array(
			'className' => 'Seo.Seo',
			'foreignKey' => 'object_id',
			'conditions' => array('Seo.object_type' => 'Category'),
			'dependent' => true
		),
		'Media' => array(
			'className' => 'Media.Media',
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'Category', 'Media.main' => 1),
			'dependent' => true
		)
	);

	public $validate = array(
        'title' => 'notBlank'
    );
}
