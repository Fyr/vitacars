<span class="descr-tabs">
    <ul class="nav nav-tabs">
        <li id="tab-by" class="active"><a href="javascript:;">BY</a></li>
        <li id="tab-ru"><a href="javascript:;">RU</a></li>
        <li id="tab-ua"><a href="javascript:;">UA</a></li>
    </ul>
    <br/>
    <?
    foreach (array('by', 'ru', 'ua') as $lang) {
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
