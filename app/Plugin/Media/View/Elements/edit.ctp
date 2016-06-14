<?
/**
 * Renders Media Widget
 * @param str $object_type
 * @param int $object_id
 */
	$this->Html->css(array('jquery.fileupload-ui', '/Media/css/media'), array('inline' => false));
	$this->Html->script(array(
	   'vendor/jquery/jquery.iframe-transport', 
	   'vendor/jquery/jquery.fileupload',
	   'vendor/tmpl.min',
	   '/Table/js/format', 
	   '/Core/js/json_handler',
	   '/Media/js/media_grid',
	   '/Media/js/media_ui'
	), array('inline' => false));
?>
	<table width="100%">
	<tr>
		<td width="25%" valign="middle">
            <span class="btn btn-primary fileinput-button">
    	        <i class="icon-plus icon-white"></i>
    	        <span><?=__d('media', 'Upload files...');?></span>
    	        <input id="fileupload" type="file" name="files[]" multiple>
    	    </span>
		</td>
		<td width="75%" align="center" valign="middle">
			<div id="progress" class="progress progress-primary progress-striped" style="margin-bottom: 0;">
                <div class="bar"></div>
            </div>
		</td>
	</tr>
	</table>
	<br/>
	<table class="media-grid" width="100%">
	<tr>
		<td class="media-thumbs" width="65%">
			<!-- thumbs content here -->
		</td>
		<td class="media-info form-horizontal" width="35%">
			<script type="text/x-tmpl" id="media-info">
			<button type="button" class="btn btn-danger pull-right" onclick="if (confirm('<?=__d('media', 'Are your sure to delete this record?')?>')) { mediaGrid.actionDelete({%=o.id%}); }"><i class="icon-white icon-trash"></i> <?=__d('media', 'Delete')?></button>
				<ul class="nav nav-tabs">
<?
	foreach(Configure::read('domains') as $tab) {
?>
					<li id="tab-<?=$tab?>"><a href="javascript:;"><?=strtoupper($tab)?></a></li>
<?
	}
?>
				</ul>
				{%
					if (o.media_type == 'image') {
				 		aLang = ['by', 'ru', 'bg'];
				 		for(var i = 0; i < aLang.length; i++) {
				 			var lang = aLang[i];

				 			var checked = (o['show_' + lang]) ? 'checked="checked"' : '';
				 			var _class = 'btn btn-success pull-right', disabled = '';
				 			if (!checked || o['main_' + lang]) {
				 				_class+= ' disabled';
				 				disabled = 'disabled="disabled"';
				 			}
				%}
				<div id="media-tab-content-{%=lang%}" class="media-tab-content" style="display: none;">
					<span class="pull-left" style="position: relative; top: 10px;">Показывать</span>
					<input type="checkbox" {%=checked%} onchange="mediaGrid.actionUpdate({%=o.id%}, {show_{%=lang%}: (this.checked) ? 1 : 0})" style="position: relative; top: 7px;"/>
					&nbsp;&nbsp;
				{%
							if (o['main_' + lang]) {
				%}
					<span class="pull-right" style="position: relative; top: 10px;"><i class="icon-ok"></i> Осн.</span>
				{%
							} else {
				%}
					<button type="button" class="{%=_class%}" {%=disabled%} onclick="mediaGrid.actionSetMain({%=o.id%}, '{%=lang%}')" style="position: relative; top: 5px;">
						<i class="icon-white icon-ok"></i> Осн.
					</button>
				{%
							}
				%}
					<br/><br/>
					Alt (.{%=lang%}):
					<input type="text" onfocus="this.select()" value="{%=o['alt_' + lang]%}" onchange="mediaGrid.actionUpdate({%=o.id%}, {alt_{%=lang%}: this.value})" style="width: 60%" />
					<span class="media-alt">
						<button type="button" class="btn"><i class="icon icon-refresh"></i></button>
					</span>
					<span class="media-alt-loader" style="display: none">
						&nbsp;&nbsp;<img src="/img/ajax_loader.gif" alt=""/>
					</span>
				</div>
				{%
				 		}
					}
				%}

				<hr style="border-color: #ddd;"/>
				<h5><?=__d('media', 'Original file')?></h5>
				<?=__d('media', 'Uploaded')?>: {%=o.created%}<br/>
				<?=__d('media', 'File name')?>: {%=o.orig_fname%}<br/>
				<?=__d('media', 'File size')?>: {%=Format.fileSize(o.orig_fsize)%}<br/>
				{% if (o.media_type == 'image') { %}
				<?=__d('media', 'Resolution')?>: {%=o.orig_w%}x{%=o.orig_h%}px<br/>
				{% } %}
				<!--button type="button" class="btn btn-mini" onclick="media_enlarge({%=o.id%})"><i class="icon-search"></i> <?=__d('media', 'Enlarge')?></button-->
				<h5><?=__d('media', 'Links')?></h5>
				<div class="media-urls">
				{% if (o.media_type == 'image') { %}
				<?=__d('media', 'Original size')?>:<br/>
				<input type="text" id="media-url-orig" value="/media/router/index/{%=(o.object_type).toLowerCase()%}/{%=o.id%}/noresize/{%=o.file%}{%=o.ext%}" readonly="readonly" onfocus="this.select()"/>
				<?=__d('media', 'For editor')?>:<br/>
				<input type="text" id="media-url-editor" value="/media/router/index/{%=(o.object_type).toLowerCase()%}/{%=o.id%}/400x/{%=o.file%}{%=o.ext%}" readonly="readonly" onfocus="this.select()" />
				{% } %}
				<?=__d('media', 'For download')?>:<br/>
				<input type="text" id="media-url-download" value="{%=o.url_download%}" readonly="readonly" onfocus="this.select()" />
				<a href="{%=o.url_download%}" target="_blank"><?=__d('media', 'Download')?></a>
				</div>
				{% if (o.media_type == 'image') { %}
				<h5><?=__d('media', 'Resize')?></h5>
				<div>
					<?=__d('media', 'Width')?> x <?=__d('media', 'Height')?>: <input type="text" id="media-w" name="" value="" onfocus="this.select()" onchange="mediaGrid.updateImageURL({%=o.id%})" /> x
					<input type="text" id="media-h" value="" onfocus="this.select()" onchange="mediaGrid.updateImageURL({%=o.id%})" />
					<button type="button" class="btn"><i class="icon icon-refresh"></i></button>
				</div>
				<?=__d('media', 'Media URL')?>: <input type="text" id="media-url" value="" readonly="readonly" onfocus="this.select()" />
				{% } %}
			</script>
			
		</td>
	</tr>
	</table>
<script type="text/javascript">
var mediaGrid = null, object_type = '<?=$object_type?>', object_id = <?=$object_id?>;
var mediaURL = {
	upload: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'upload'))?>',
	move: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'move'))?>.json',
	list: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'getList', $object_type, $object_id))?>.json',
	delete: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'delete', $object_type, $object_id))?>/{$id}.json',
	setMain: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'setMain', $object_type, $object_id))?>/{$id}/{$lang}.json',
	update: '<?=$this->Html->url(array('plugin' => 'media', 'controller' => 'ajax', 'action' => 'update', $object_type, $object_id))?>/{$id}.json'
};
var mediaLocale = {
	noFiles: '<?=__d('media', 'No media files found')?>',
	noData: '<?=__d('media', 'No media data')?>'
}
</script>