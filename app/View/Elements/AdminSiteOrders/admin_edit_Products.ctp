<?
    $viewURL = $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.detail_num' => '')).'~{$id}';
    $title = __('View Product');
    foreach ($aRows as &$row) {
        $detail = $row['SiteOrderDetails'];
        $code = $row['Product']['code'];
        $url = str_replace('{$id}', $code, $viewURL);
        $row['Product']['code'] = $this->Html->link($code, $url, array('title' => $title));
        $row['SiteOrderDetails']['price'] = ($detail['price'] > 0) ? $this->Price->calcPrice($detail['price'], $detail['discount']) : '';
        $row['SiteOrderDetails']['discount'] = ($detail['discount'] > 0) ? -$detail['discount'].'%' : '';
    }


    $columns = $this->PHTableGrid->getDefaultColumns('SiteOrderDetails');
    // unset($columns['Product.*']);
    echo $this->PHTableGrid->render('SiteOrderDetails', array(
		'actions' => array(
			'table' => array(),
			'row' => array(
			    // 'view' => $this->Html->link('', $viewURL, array('class' => 'icon-color icon-preview', 'title' => $title))
			),
			'checked' => array()
		),
		'data' => $aRows,
		'columns' => $columns,
	));
