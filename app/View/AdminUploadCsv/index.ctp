<div class="span8 offset2">
<?
	$title = 'Загрузка CSV';
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('UploadCsv', array(
		'url' => array(
			'controller' => 'AdminUploadCsv', 
			'action' => 'upload'
			), 
        'method' => 'POST',
        'enctype' => 'multipart/form-data'
	));
	echo $this->element('admin_content');
	echo $this->element('/AdminUploadCsv/admin_upload_csv_form');
	echo $this->element('admin_content_end');
	$backURL = $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'index'));
?>
    <table class="form-3actions" width="100%">
    <tr>
        <td width="30%">
            <a href="<?=$backURL?>" class="btn"><i class="icon-chevron-left"></i> <?=__('Back')?></a>
        </td>
        <td width="40%" align="center">
        </td>
        <td width="30%">
            <?=$this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'))?>
        </td>
    </tr>
    </table>
<?
    echo $this->PHForm->end();
?>
</div>