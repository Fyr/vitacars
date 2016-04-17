<?
	$this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect', '/Table/css/grid', '/Icons/css/icons', 'the-modal'), array('inline' => false));
	$this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect', 'vendor/jquery/jquery.the-modal'), array('inline' => false));

	if (isset($gpzError)) {
		echo $this->element('admin_content');
?>
	<div style="color: #f00; padding: 20px 0;">
		<b>Ошибка!</b><br />
		<?=$gpzError?>
	</div>
<?
		echo $this->element('admin_content_end');
	} elseif (isset($gpzData) && !$gpzData) {
		echo $this->element('admin_content');
?>
	<div style="padding: 20px 0;">
		По данному запросу результатов не найдено
	</div>
<?
		echo $this->element('admin_content_end');
	} elseif (isset($gpzData) && $gpzData) {
?>
		<div align="left" style="margin-bottom: 10px;">
			Сортировать по <?=$this->Form->input('sort', array('options' => $aSorting, 'value' => $sort, 'div' => false, 'label' => false))?>
			<?=$this->Form->input('order', array('options' => $aOrdering, 'value' => $order, 'div' => false, 'label' => false))?>
		</div>

		<table align="left" class="grid table-bordered shadow" border="0" cellpadding="0" cellspacing="0">
		<thead>
		<tr class="first table-gradient">
			<th></th>
			<th>
				<a class="grid-unsortable" href="javascript:void(0)">Лого</a>
			</th>
<?
		foreach($aSorting as $key => $title) {
			$class = ($sort == $key) ? 'grid-sortable-active grid-sortable-'.$order : '';
			$dir = ($sort == $key && $order == 'asc') ? 'desc' : 'asc';
			if ($key == 'image') {
?>
			<th>
				<a class="grid-unsortable" href="javascript:void(0)"><?=$title?></a>
			</th>
<?
			} else {
?>
			<th>
				<a class="grid-sortable <?=$class?>" href="javascript:;" title="Сортировать по `<?=$title?>` <?=$aOrdering[$dir]?>" onclick="sortBy('<?=$key?>', '<?=$dir?>')"><?=$title?></a>
			</th>
<?
			}
		}
		if ($lFullInfo) {
?>
			<th>
				<a class="grid-unsortable" href="javascript:void(0)">Провайдер</a>
			</th>
			<th>
				<a class="grid-unsortable" href="javascript:void(0)">Продавец</a>
			</th>
<?
		}
		$colspan = ($lFullInfo) ? 10 : 8;
?>
		</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="<?=$colspan?>" class="subheader">
					<b>Запрашиваемый номер и возможные замены (кроссы)</b>
				</td>
			</tr>
<?
		foreach($gpzData as $i => $row) {
?>
			<tr class="grid-row">
				<td>
<?
			if ($row['provider'] == 'TechDoc' && isset($row['provider_data']['criteria']) && $params = $row['provider_data']['criteria']) {
?>
					<a class="icon-color icon-info popup-trigger" href="javascript:void(0)" onclick="$('#partnumber<?=$i?>').modal().open();"></a>
					<div class="modal" id="partnumber<?=$i?>" style="display: none">
						<span class="popup-close">&times;</span>
						<h3><?=$row['partnumber']?> <?=$row['title']?> </h3>
						<div align="center" style="margin: 10px 0">
							<?=($row['image']) ? $this->Html->image($row['image'], array('alt' => h($row['title']), 'style' => 'max-width: 75%')) : ''?>
						</div>
						<b>Технические характеристики</b><br/>
<?
				foreach($params as $param) {
?>
						<?=$param['key']?>: <?=$param['value']?><br/>
<?
				}
?>
					</div>
<?
			}
?>
				</td>
				<td>
					<?=($row['brand_logo']) ? $this->Html->image($row['brand_logo'], array('class' => 'brand-logo')) : ''?>
				</td>
				<td>
					<?=$row['brand'];?>
				</td>
				<td nowrap="nowrap">&nbsp;<?=$row['partnumber']?></td>
				<td>
<?
	if ($row['image']) {
?>
					<a class="fancybox" href="<?=$row['image']?>" rel="photo">
						<?=($row['image']) ? $this->Html->image($row['image'], array('class' => 'product-img')) : ''?>
					</a>
<?
	}
?>
				</td>
				<td>
					<?=$row['title']?>
					<?//$row['title_descr']?>
				</td>
				<td>
					<b><?=$row['qty']?></b>
					<?=(!$lFullInfo && $row['qty_order']) ? '<br />'.$row['qty_order'].' дн.' : ''?>
					<?=($lFullInfo && trim($row['qty_descr'])) ? '<br />'.$row['qty_descr'] : ''?>
				</td>
				<td align="right" <? if (!$lFullInfo) {?> nowrap="nowrap" <? }?>>
					<b><?=$this->Price->format($row['price2'])?></b>
<?
					if ($lFullInfo) {
?>
					<br/>
					Цена без/н.: <?=$this->Price->format($row['price'])?> (<?=$row['price_orig']?>)<br/>
					<?=$row['price_descr']?>
<?
					}
?>
				</td>
<?
					if ($lFullInfo) {
?>
				<td align="center"><?=($row['provider'] == 'Zzap') ? 'ZAP' : $row['provider']?></td>
				<td><?=$row['provider_descr']?></td>
<?
					}
?>
			</tr>
<?
		}
?>
		</tbody>
		</table>
<?
	}
?>
</div>
<style type="text/css">
	.grid .subheader {
		background: #ddd none repeat scroll 0 0;
		font-size: 14px;
		font-weight: bold;
		padding: 10px 0 5px 10px;
	}
</style>
<script type="text/javascript">
function sortBy(key, dir) {
	var url = location.href;
	if (url.indexOf('sort') > -1) {
		url = url.replace(/sort=\w+/, 'sort=' + key);
	} else {
		url+= '&sort=' + key;
	}
	if (url.indexOf('order') > -1) {
		url = url.replace(/order=\w+/, 'order=' + dir);
	} else {
		url+= '&order=' + dir;
	}
	
	location.href = url;
}

$(function(){
	$('#sort, #order').change(function(){
		sortBy($('#sort').val(), $('#order').val());
	});
	
	$('.fancybox').fancybox({
		padding: 5
	});

	$('.popup-close').on('click', function(e){
		$.modal().close();
	});
});
</script>