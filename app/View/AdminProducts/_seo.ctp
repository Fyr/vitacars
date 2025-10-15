<span class="descr-tabs">
<?
    echo $this->element('lang_tabs');
	$field = (isset($field)) ? $field : 'body';
	foreach(Configure::read('domains') as $lang) {
?>
	<div id="descr-tab-content-<?=$lang?>" class="descr-tab-content">
<?
	echo $this->PHForm->input('Seo.title_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Title')));
	echo $this->PHForm->input('Seo.keywords_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Keywords')));
	echo $this->PHForm->input('Seo.descr_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Description')));
?>
	</div>
<?
	}
?>
</span>
