function initTabs() {
    $('ul#navTabs > li > a').click(function(){
        var tab = $(this).parent().get(0).id.replace(/tab\-/, '');
        $('ul#navTabs > li').removeClass('active');
        $('ul#navTabs > #tab-' + tab).addClass('active');
        $('.tab-content-all .tab-content').hide();
        $('.tab-content-all #tab-content-' + tab).show();
    });
}

$(document).ready(function(){
    initTabs();
    var url = window.location.href;
    if (url.indexOf('#tab-') > 0) {
        var a = url.split('#');
        var tabID = a[1];
        $('ul#nav #' + tabID + ' a').click();
    }
});
