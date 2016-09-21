<div class="span8 offset2">
<?
    $title = 'Product parser';
    echo $this->element('admin_title', compact('title'));
    echo $this->element('admin_content');
    echo $this->element('progress', $task);
    echo $this->element('admin_content_end');
?>
</div>
