<?
    $viewURL = $this->Html->url(array('action' => 'viewProduct')).'/{$id}';
    $title = __('View Product');
    foreach ($aRows as &$row) {
        $code = $row['Product']['code'];
        $url = str_replace('{$id}', $code, $viewURL);
        $row['Product']['code'] = $this->Html->link($code, $url, array('title' => $title));
    }


    $columns = $this->PHTableGrid->getDefaultColumns('SiteOrderDetails');
    // unset($columns['Product.*']);
    echo $this->PHTableGrid->render('SiteOrderDetails', array(
		'actions' => array(
			'table' => array(),
			'row' => array(
			    'view' => $this->Html->link('', $viewURL, array('class' => 'icon-color icon-preview', 'title' => $title))
			),
			'checked' => array()
		),
		'data' => $aRows,
		'columns' => $columns,
	));
