<?
	echo $this->element('Form.form_fields');
	echo $this->PHForm->hidden('User.field_rights', array('value' => $this->request->data('User.field_rights')));
?>
<script type="text/javascript">
$(document).ready(function(){
	var $grid = $('#grid_FormField');
	
	var vals = $('#UserFieldRights').val().split(',');
	for(var i = 0; i < vals.length; i++) {
		$('.grid-chbx-row[value=' + vals[i] + ']', $grid).click();
	}
	$('.form-3actions button[type=submit]').click(function(){
		var vals = [];
		$('.grid-chbx-row:checked', $grid).each(function(){
			vals.push($(this).val());
		});
		$('#UserFieldRights').val(vals.join(','));
	});
});
</script>
