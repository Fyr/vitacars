<div class="span8 offset2">
<?
    $title = 'Veles-Torg.by Parser';
    echo $this->element('admin_title', compact('title'));
    echo $this->element('admin_content');
    echo $this->element('progress', compact('task'));
    echo $this->element('admin_content_end');
?>
</div>
