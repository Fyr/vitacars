<span class="descr-tabs">
    <ul class="nav nav-tabs">
<?
    foreach(Configure::read('domains') as $lang) {
?>
        <li id="tab-<?=$lang?>"><a href="javascript:;"><?=strtoupper($lang)?></a></li>
<?
    }
?>

    </ul>
    <br/>
<?
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
<script>
function descr_activateTab(tab) {
    var context = $('.descr-tabs');
    $('ul.nav.nav-tabs > li', context).removeClass('active');
    $('ul.nav.nav-tabs > #tab-' + tab, context).addClass('active');
    $('.descr-tab-content', context).hide();
    $('#descr-tab-content-' + tab, context).show();
}
$(function(){
    descr_activateTab('<?=Configure::read('domains')[0]?>');
    $('.descr-tabs ul.nav.nav-tabs > li').click(function(){
        var tab = $(this).prop('id').replace(/tab-/, '');
        descr_activateTab(tab);
    });
});
</script>
