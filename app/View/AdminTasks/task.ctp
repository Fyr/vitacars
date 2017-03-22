<div class="span8 offset2">
<?
	echo $this->element('admin_title', compact('title'));
	if ($task) {
		echo $this->element('admin_content');
		echo $this->element('progress', compact('task'));
		echo $this->element('admin_content_end');
	} else {
		echo $this->element('AdminTasks/preprocess_'.$taskName);
	}

?>
</div>