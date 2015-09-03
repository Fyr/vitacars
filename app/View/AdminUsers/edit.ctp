<div class="span8 offset2">
<?
    $id = $this->request->data('User.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('User');
	$aTabs = array(
		'General' => $this->element('/AdminUsers/admin_edit_User'),
		'Contacts' => $this->element('/AdminUsers/admin_edit_UserContacts'),
		'Rights' => $this->element('/AdminUsers/admin_edit_UserRights'),
		'Brands' => $this->element('/AdminUsers/admin_edit_UserBrands'),
	);
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
$(document).ready(function(){
	var $grid = $('#grid_FormField');
	var $grid2 = $('#grid_Brand');
	
	var vals = $('#UserFieldRights').val().split(',');
	for(var i = 0; i < vals.length; i++) {
		$('.grid-chbx-row[value=' + vals[i] + ']', $grid).click();
	}
	
	var vals = $('#UserBrandRights').val().split(',');
	for(var i = 0; i < vals.length; i++) {
		$('.grid-chbx-row[value=' + vals[i] + ']', $grid2).click();
	}
	
	$('.form-3actions button[type=submit]').click(function(){
		var vals = [];
		$('.grid-chbx-row:checked', $grid).each(function(){
			vals.push($(this).val());
		});
		$('#UserFieldRights').val(vals.join(','));
		
		var vals = [];
		$('.grid-chbx-row:checked', $grid2).each(function(){
			vals.push($(this).val());
		});
		$('#UserBrandRights').val(vals.join(','));
	});
	
});
</script>