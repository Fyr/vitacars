<?
	$A1 = Configure::read('Params.A1');
	$A2 = Configure::read('Params.A2');
?>
    <table>
        <thead>
            <tr>
                <th><?=__('Title rus')?></th>
                <th><?=__('Code')?></th>
                <th><?=$aParams[$A1]['PMFormField']['label']?></th>
                <th><?=$aParams[$A2]['PMFormField']['label']?></th>
            </tr>
        </thead>
        <tbody>
<?
	$class = 'even';
	foreach($aRowset as $Product) { 
		$class = ($class == 'even') ? 'odd' : 'even';
?>
		<tr class="row">
			<td class="<?=$class?>"><?=$Product['Product']['title_rus']?></td>
			<td class="<?=$class?>"><?=$Product['Product']['code']?></td>
			<td class="<?=$class?>"><?=$Product['PMFormData']['fk_'.$A1]?></td>
			<td class="<?=$class?>"><?=$Product['PMFormData']['fk_'.$A2]?></td>
		</tr>
<?php 
	}
?>
        </tbody>
    </table>
    