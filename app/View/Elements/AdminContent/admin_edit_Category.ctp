<?
	echo $this->element('Article.edit_title');
	echo $this->element('Article.edit_slug');
	echo $this->PHForm->input('sorting', array('class' => 'input-small'));
	echo $this->PHForm->input('is_subdomain', array('label' => array('class' => 'control-label', 'text' => 'Отдельный субдомен')));
	echo $this->PHForm->input('export_bg', array('label' => array('class' => 'control-label', 'text' => 'Экспорт для .BG')));
