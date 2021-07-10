<div class="span8 offset2">
<?
	$title = __('Search by period');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('Report', array('class' => 'form-inline'));
	echo $this->element('admin_content');
?>
	<div class="control-group">
		<?=__('Period')?> <input type="text" class="input-small date" name="data[date]" value="<?=$this->request->data('date')?>" />&nbsp;&nbsp;
		<input type="text" class="input-small date" name="data[date2]" value="<?=$this->request->data('date2')?>" />&nbsp;&nbsp;
		<?=__('Min.qty')?> <input type="text" class="input-small" name="data[minQty]" value="<?=$this->request->data('minQty')?>" />
		<?=__('Max.qty')?> <input type="text" class="input-small" name="data[maxQty]" value="<?=$this->request->data('maxQty')?>" />
		&nbsp;&nbsp;<?=$this->PHForm->submit(__('Apply').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success', 'name' => 'apply', 'value' => 'apply'))?>
	</div>
<?
	if (!isset($rows) && $this->request->is('post')) {
?>
	<div>
		За указанный период поисковых запросов не найдено
	</div>
<?
	}
	if (isset($rows)) {
		$this->Html->css(array('/Table/css/grid'), array('inline' => false));
		$url = $this->Html->url(array('controller' => 'AdminProducts', 'Product.id' => 'list'));
?>
	<div>
		Найдено <a href="<?=$url?>"><?=count($aProducts)?> продуктов</a><br/>
		Всего: <?=count($queries)?> поисковых запросов
	</div>
	<table class="grid table-bordered shadow" width="95%">
	<thead>
		<tr class="first table-gradient">
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)">Кол-во поисков</a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Brand')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Code')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Title rus')?></a>
			</th>
		</tr>
	</thead>
	<tbody>
<?
		foreach($rows as $row) {
			if (isset($aProducts[$row['product_id']])) { // почемуто иногда попадаются продукты id-шников которых нету в БД - возможно продукты удалили или какой-то хитрый баг
				$article = $aProducts[$row['product_id']];
				$url = $this->Html->url(array('controller' => 'AdminProducts', 'Product.id' => $row['product_id']));
?>
		<tr class="grid-row">
			<td class="text-center"><?=$row['qty']?></td>
			<td class="text-left"><?=$article['Brand']['title']?></td>
			<td class="text-center"><?=$this->Html->link($article['Product']['code'], $url)?></td>
			<td class="text-left"><?=$this->Html->link($article['Product']['title_rus'], $url)?></td>
		</tr>
<?
			}
		}
?>

		<tr class="grid-footer table-gradient" id="last-tr">
			<td colspan="4" style="padding: 5px">&nbsp;</td>
		</tr>
	</tbody>
	</table>
<?
	}
	echo $this->element('admin_content_end');
	echo $this->PHForm->end();
?>
</div>