<span class="descr-tabs">
<?
    echo $this->element('lang_tabs');
    $field = (isset($field)) ? $field : 'body';
    foreach(Configure::read('domains') as $lang) {
        $tab = '_' . $lang;
?>
    <div id="descr-tab-content-<?=$lang?>" class="descr-tab-content">
<?
        if (isset($teaser) && $teaser) {
            echo $this->PHForm->input('teaser'.$tab);
        }
        echo $this->element('Article.edit_body', array('field' => $field.$tab));
?>
    </div>
<?
    }
?>
</span>
