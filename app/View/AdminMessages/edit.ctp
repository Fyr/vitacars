<div class="span8 offset2">
<?
    $id = $this->request->data('Notify.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create($objectType);
	$aTabs = array(
		'Message' => $this->element('/AdminMessages/admin_edit_General'),
		'Recipients' => $this->element('/AdminMessages/admin_edit_Users'),
	);
	echo $this->element('admin_tabs', compact('aTabs'));
?>
	<table class="form-3actions" width="100%">
		<tr>
			<td width="30%">
				<a href="<?=$this->Html->url(array('action' => 'index'))?>" class="btn"><i class="icon-chevron-left"></i> <?=__('Back')?></a>
			</td>
			<td width="40%" align="center">
				<?=$this->PHForm->submit('<i class="icon-white icon-ok"></i> '.__('Save'), array('class' => 'btn btn-primary', 'name' => 'save', 'value' => 'save'))?>
			</td>
			<td width="30%">
				<?=$this->PHForm->submit('Отправить <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-danger pull-right', 'name' => 'send', 'value' => 'send'))?>
			</td>
		</tr>
	</table>
<?
    echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
$(document).ready(function(){
	var $grid = $('#grid_User');

	var vals = $('#NotifyUsers').val().split(',');
	for(var i = 0; i < vals.length; i++) {
		$('.grid-chbx-row[value=' + vals[i] + ']', $grid).click();
	}

	$('.form-3actions button[type=submit]').click(function(){
		var vals = [];
		$('.grid-chbx-row:checked', $grid).each(function(){
			vals.push($(this).val());
		});
		$('#NotifyUsers').val(vals.join(','));
	});
});
</script>