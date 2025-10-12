<?
	echo $this->element('Article.edit_title');
	echo $this->element('Article.edit_slug');
	echo $this->PHForm->input('sorting', array('class' => 'input-small'));
	echo $this->PHForm->input('export_by');
	echo $this->PHForm->input('export_ru');
	// echo $this->PHForm->input('status', array('label' => false, 'multiple' => 'checkbox', 'options' => array('published' => __('Published')), 'class' => 'checkbox inline'));
