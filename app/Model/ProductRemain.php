<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
class ProductRemain extends AppModel {
	
	public $belongsTo = array(
		'Product' => array(
			'foreignKey' => 'product_id'
		)
	);
	
	public function sales($date, $date2) {
		$fields = array('SUM(remain) AS sum_remain', 'Product.*');
    	$conditions = $this->dateRange('ProductRemain.created', $date, $date2); 
    	$conditions[] = 'remain < 0';
    	$group = array('product_id');
    	return $this->find('all', compact('fields', 'conditions', 'group'));
	}
}
