<?
	foreach(array('by', 'ru') as $lang) {
?>
<fieldset class="fieldset">
	<legend><?=strtoupper($lang)?></legend>

<?
	echo $this->PHForm->input('Seo.title_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Title')));
	echo $this->PHForm->input('Seo.keywords_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Keywords')));
	echo $this->PHForm->input('Seo.descr_'.$lang, array('type' => 'text', 'label' => array('class' => 'control-label', 'text' => 'Description')));
?>
</fieldset>
<?
	}
?>