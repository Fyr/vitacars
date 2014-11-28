<?
	$form = array_chunk($form, ceil(count($form) / 2));
?>
<table>
<tbody>
<tr>
	<td valign="top"><?=$this->PHFormFields->render($form[0], $formValues)?></td>
	<td valign="top"><?=$this->PHFormFields->render($form[1], $formValues, count($form[0]))?></td>
</tr>
</tbody>
</table>