<?=$this->element('admin_title', array('title' => __('Export')))?>
<div class="span8 offset2">
<?=$this->element('admin_content')?>
Экспортировано по типам записей:<br />
<?
	foreach($counter as $object_type => $_count) {
		echo '&nbsp;&nbsp;'.$object_type.': '.$_count.'<br />';
	}
?>
<?=$this->element('admin_content_end')?>
</div>