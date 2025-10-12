<?
App::uses('AppModel', 'Model');
class Subcategory extends AppModel {
    public $belongsTo = array(
        'Category' => array(
            'foreignKey' => 'cat_id',
            'dependent' => true
        )
    );

    public $hasOne = array(
		'Seo' => array(
			'className' => 'Seo.Seo',
			'foreignKey' => 'object_id',
			'conditions' => array('Seo.object_type' => 'Subcategory'),
			'dependent' => true
		),
		'Media' => array(
			'className' => 'Media.Media',
			'foreignKey' => 'object_id',
			'conditions' => array('Media.object_type' => 'Subcategory', 'Media.main' => 1),
			'dependent' => true
		)
	);

	public $validate = array(
        'title' => 'notBlank'
    );
}
