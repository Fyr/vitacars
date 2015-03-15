	<table class="grid table-bordered shadow">
	<thead>
		<tr class="first table-gradient">
			<th class="nowrap">CSV <?=($keyField == 'detail_num') ? __('Detail num') : __('Code')?></th>
			<th class="nowrap"><?=__('Code')?></th>
			<th class="nowrap"><?=__('Detail num')?></th>
			<th class="nowrap"><?=__('Title rus')?></th>
		</tr>
	</thead>
	<tbody>
<?
	echo $this->element('/AdminUploadCsv/print_products');
?>
	</tbody>
	</table>
