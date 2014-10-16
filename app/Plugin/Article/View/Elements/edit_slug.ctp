<?
    $this->Html->script(array('/Article/js/translit_utf', '/Article/js/edit_slug'), array('inline' => false));
	echo $this->PHForm->input('page_id', array('type' => 'text', 'onchange' => 'article_onChangeSlug()', 'label' => array('text' => __('Slug'), 'class' => 'control-label')));
	// echo $this->PHForm->hidden('page_id');
?>
<script type="text/javascript">
var slug_EditMode = <?=(($this->request->data($this->PHForm->defaultModel.'.page_id'))) ? 'true' : 'false'?>; // <?=$this->PHForm->defaultModel?>

</script>

