<?
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
class ProductRemain extends AppModel {

	public function sales($date, $date2) {
		$fields = array('SUM(IF(remain < 0, remain, 0)) AS sum_outcome', 'SUM(IF(remain > 0, remain, 0)) AS sum_income', 'product_id');
    	$conditions = $this->dateRange('ProductRemain.created', $date, $date2); 
    	$group = array('product_id');
    	return $this->find('all', compact('fields', 'conditions', 'group'));
	}
}
