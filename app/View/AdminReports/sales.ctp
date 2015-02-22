<div class="span8 offset2">
<?
	$title = __('Sales by period');
	echo $this->element('admin_title', compact('title'));
	if (isset($rows)) {
		$this->Html->css(array('/Table/css/grid'), array('inline' => false));
?>
	<div class="text-center">
		<form id="printForm" action="" method="post">
			<input type="hidden" name="data[print]" value="1" />
			<input type="hidden" name="data[date]" value="<?=$this->request->data('date')?>" />
			<input type="hidden" name="data[date2]" value="<?=$this->request->data('date2')?>" />
			<button type="submit" class="btn btn-primary"><i class="icon-white icon-print"></i> Распечатать</button>
		</form>
	</div>
	<?=$this->element('admin_content')?>
	<div align="center">
		<?=__('Period')?> <?=($this->request->data('date')) ? 'c '.$this->request->data('date') : ''?> <?=($this->request->data('date2')) ? 'по '.$this->request->data('date2') : ''?>
	</div>
	<table class="grid table-bordered shadow" width="95%">
	<thead>
		<tr class="first table-gradient">
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Code')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Title rus')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Sold')?></a>
			</th>
		</tr>
	</thead>
	<tbody>
<?
		$total = 0;
		foreach($rows as $row) {
			$qty = abs($row[0]['sum_remain']);
			$total+= $qty;
?>
		<tr class="grid-row">
			<td class="text-left"><?=$row['Product']['code']?></td>
			<td class="text-left"><?=$row['Product']['title_rus']?></td>
			<td class="text-right"><?=$qty?></td>
		</tr>
<?
		}
?>

		<tr class="grid-footer table-gradient" id="last-tr">
			<td></td>
			<td align="right">
				<b><?=__('Total')?>:</b>
			</td>
			<td style="padding: 5px; text-align: right"><b><?=$total?></b></td>
		</tr>
	</tbody>
	</table>
<?
		echo $this->element('admin_content_end');
	} else {
?>
<?
	echo $this->PHForm->create('Report', array('class' => 'form-inline'));
	echo $this->element('admin_content');
	/*
	echo $this->PHForm->input('date', array('class' => 'input-small date'));
	echo $this->PHForm->input('date2', array('class' => 'input-small date'));
	*/
?>
	<div class="control-group">
		<?=__('Period')?> <input type="text" class="input-small date" name="data[date]" value="" />&nbsp;&nbsp;
		<input type="text" class="input-small date" name="data[date2]" value="" />
	</div>
<?
	echo $this->element('admin_content_end');
	echo $this->PHForm->submit(__('Apply').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success', 'name' => 'apply', 'value' => 'apply'));
	echo $this->PHForm->end();
?>
	</form>
<?
	}
?>
</div>