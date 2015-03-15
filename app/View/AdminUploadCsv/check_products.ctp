<div class="span8 offset2">
<?
	$title = __('Check products');
	echo $this->element('admin_title', compact('title'));
	if (isset($aCodes)) {
		$this->Html->css(array('/Table/css/grid'), array('inline' => false));
?>
	<div class="text-center">
		<form action="" method="post">
			<input type="hidden" name="data[codes]" value="<?=implode(',', $aCodes)?>" />
			<input type="hidden" name="data[print]" value="1" />
			<input type="hidden" name="data[keyField]" value="<?=$keyField?>" />
			<button type="submit" class="btn btn-primary"><i class="icon-white icon-print"></i> Распечатать</button>
		</form>
	</div>
	<?=$this->element('admin_content')?>
	<table class="grid table-bordered shadow" width="95%">
	<thead>
		<tr class="first table-gradient">
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)">CSV <?=($keyField == 'detail_num') ? __('Detail num') : __('Code')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Code')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Detail num')?></a>
			</th>
			<th class="nowrap">
				<a class="grid-unsortable" href="javascript:void(0)"><?=__('Title rus')?></a>
			</th>
		</tr>
	</thead>
	<tbody>
<?
	echo $this->element('/AdminUploadCsv/print_products');
?>
		<tr class="grid-footer table-gradient" id="last-tr">
			<td class="nowrap" colspan="4">
				<table>
				<tbody>
					<tr><td class="grid-checked-actions"><div class="hide"><small></small><div class="btn-group"><a href="#" data-toggle="dropdown" class="btn dropdown-toggle btn-mini"><span class="caret"></span></a><ul class="dropdown-menu"><li><a onclick="undefined" href="javascript:void(0)" class=""><i class="icon-color icon-delete"></i>Удалить помеченные записи</a></li></ul></div></div></td><td class="text-center grid-paging"></td><td class="text-right grid-records-count"></td></tr>
				</tbody>
				</table>
			</td>
		</tr>
	</tbody>
	</table>
<?
		echo $this->element('admin_content_end');
	} else {
		echo $this->PHForm->create('UploadCsv', array(
			'url' => array(
				'controller' => 'AdminUploadCsv', 
				'action' => ''
			), 
	        'method' => 'POST',
	        'enctype' => 'multipart/form-data'
		));
		echo $this->element('/AdminUploadCsv/admin_upload_csv_form');
	    echo $this->PHForm->end();
	}
?>
</div>