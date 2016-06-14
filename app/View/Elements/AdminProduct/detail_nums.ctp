<span class="detail-nums">
    <a href="javascript:;" class="btn btn-mini expand-num"> + <?=count($detail_nums)?> номер(ов) <b class="caret"></b></a>
    <div style="display: none">
<?
    foreach($detail_nums as $num) {
?>
    <?=$num?><br/>
<?
    }
?>
    </div>
    <a href="javascript:;" class="btn btn-mini collapse-num" style="display: none;"> свернуть <span class="dropup  pull-right"><span class="caret"></span></span></a>
</span>