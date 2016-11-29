<span class="descr-tabs">
    <ul class="nav nav-tabs">
        <li id="tab-by" class="active"><a href="javascript:;">BY</a></li>
        <li id="tab-ru"><a href="javascript:;">RU</a></li>
        <li id="tab-ua"><a href="javascript:;">UA</a></li>
    </ul>
    <br/>
<?
    foreach(array('by', 'ru', 'ua') as $lang) {
        $tab = ($lang == 'by') ? '' : '_' . $lang;
?>
    <div id="descr-tab-content-<?= $lang ?>" class="descr-tab-content">
<?
        if (isset($teaser) && $teaser) {
            echo $this->PHForm->input('teaser'.$tab);
        }
        echo $this->element('Article.edit_body', array('field' => 'body'.$tab));
?>
    </div>
<?
    }
?>
</span>
<script>
function descr_activateTab(tab) {
    var context = $('.descr-tabs');
    $('ul.nav.nav-tabs > li', context).removeClass('active');
    $('ul.nav.nav-tabs > #tab-' + tab, context).addClass('active');
    $('.descr-tab-content', context).hide();
    $('#descr-tab-content-' + tab, context).show();
}
$(function(){
    descr_activateTab('by');
    $('.descr-tabs ul.nav.nav-tabs > li').click(function(){
        var tab = $(this).prop('id').replace(/tab-/, '');
        descr_activateTab(tab);
    });
});
</script>