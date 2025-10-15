<span class="descr-tabs">
<?
    echo $this->element('lang_tabs');
    foreach (Configure::read('domains') as $lang) {
?>
        <div id="descr-tab-content-<?= $lang ?>" class="descr-tab-content">
<?
            echo $this->PHForm->input('Product.featured_' . $lang, array('label' => array('text' => 'На главную', 'class' => 'control-label')));
?>
        </div>
<?
    }
?>
</span>
