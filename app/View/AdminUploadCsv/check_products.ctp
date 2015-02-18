<div class="span8 offset2">
<?
	$title = __('Check products');
	echo $this->element('admin_title', compact('title'));
	if (isset($aCodes)) {
		$this->Html->css(array('/Table/css/grid'), array('inline' => false));
?>
	<div class="text-center">
		<form id="printCheckProducts" action="<?=$this->Html->url(array('controller' => 'AdminUploadCsv', 'action' => 'printCheckProducts'))?>" method="post">
			<input type="hidden" name="data[codes]" value="<?=implode(',', $aCodes)?>" />
			<button type="submit" class="btn btn-primary"><i class="icon-white icon-print"></i> Распечатать</button>
		</form>
	</div>
	<?=$this->element('admin_content')?>
	<table class="grid table-bordered shadow" width="95%">
	<thead>
		<tr class="first table-gradient">
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
		foreach($aCodes as $code) {
			$product = Hash::get($aProducts, $code);
			if ($product) {
?>
		<tr class="grid-row">
			<td class="text-left"><?=$product['Product']['code']?></td>
			<td class="text-left"><?=$product['Product']['title_rus']?></td>
		</tr>
<?
			} else {
?>
		<tr class="grid-row legend-red">
			<td class="text-left"><?=$code?></td>
			<td class="text-center">- <?=__('Product %s not found', $code)?> -</td>
		</tr>
<?
			}
		}
?>
		<tr class="grid-footer table-gradient" id="last-tr">
			<td class="nowrap" colspan="2">
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