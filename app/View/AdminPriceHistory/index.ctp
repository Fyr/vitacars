<?
$title = __('Prices history');
$actions = $this->PHTableGrid->getDefaultActions('PriceHistory');
unset($actions['table']['add']);
$actions['row'] = array();

$aProductOptions = array();
foreach ($aProducts as $article) {
    $brand = $article['Brand']['title'];
    $article = $article['Product'];
    $aProductOptions[$article['id']] = $article['code'] . ' ' . $article['title_rus'] . ' (' . $brand . ')';
}

$columns = $this->PHTableGrid->getDefaultColumns('PriceHistory');
$columns['PriceHistory.product_id']['label'] = 'Наименование детали';
$columns['PriceHistory.product_id']['format'] = 'select';
$columns['PriceHistory.product_id']['options'] = $aProductOptions;

$columns['PriceHistory.fk_id']['label'] = 'Тех.параметр';
$columns['PriceHistory.fk_id']['format'] = 'select';
$columns['PriceHistory.fk_id']['options'] = $aFormFields;

foreach ($data as &$row) {
    $row['PriceHistory']['old_price'] = round($row['PriceHistory']['old_price'], 2);
    $row['PriceHistory']['new_price'] = round($row['PriceHistory']['new_price'], 2);
}
/*
    $cols = $this->PHTableGrid->getDefaultColumns('UserLog');

    $cols['UserLog.user_id']['label'] = __('Username');
    $cols['UserLog.user_id']['format'] = 'select';
    $cols['UserLog.user_id']['options'] = $aUsers;

    $cols['UserLog.event_type']['format'] = 'select';
    $cols['UserLog.event_type']['options'] = EventType::getTypes();

    $cols['UserLog.ip']['label'] = 'IP адрес';
    $cols['UserLog.host']['label'] = 'Адрес хоста';
    $cols['UserLog.xdata']['label'] = 'Доп.данные';
*/
echo $this->element('admin_title', compact('title'));
echo $this->PHTableGrid->render('PriceHistory', compact('actions', 'columns', 'data'));
?>