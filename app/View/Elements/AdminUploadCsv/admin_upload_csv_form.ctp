<?=$this->element('admin_content')?>
<br />
<?=$this->PHForm->input(__('Select file'), array('class' => 'input-medium', 'type' => 'file', 'name' => 'csv_file', 'id' => 'csv_file'))?>
<br />
<?=$this->element('admin_content_end')?>
<?=$this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'))?>
<br/>