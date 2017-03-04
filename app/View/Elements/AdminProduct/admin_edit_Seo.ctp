<span class="descr-tabs">
    <ul class="nav nav-tabs">
		<li id="tab-by" class="active"><a href="javascript:;">BY</a></li>
		<li id="tab-ru"><a href="javascript:;">RU</a></li>
		<li id="tab-ua"><a href="javascript:;">UA</a></li>
	</ul>
    <br/>
	<?
	$field = (isset($field)) ? $field : 'body';
	foreach(array('by', 'ru', 'ua') as $lang) {
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
