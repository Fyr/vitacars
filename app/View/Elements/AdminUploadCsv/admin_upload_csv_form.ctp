<br /><br />
<?php
echo $this->PHForm->input(__('Select file'), array('class' => 'input-medium', 'type' => 'file', 'name' => 'csv_file', 'id' => 'csv_file'));
?>
<div style="text-align:center">
<?php echo $this->Session->flash(); ?>
</div>
<br />