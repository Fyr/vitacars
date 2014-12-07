<?
	$form = array_chunk($form, ceil(count($form) / 2));
?>
<table>
<tbody>
<tr>
	<td valign="top"><?=$this->PHFormData->render($form[0], $this->request->data('PMFormData'))?></td>
	<td valign="top"><?=$this->PHFormdata->render($form[1], $this->request->data('PMFormData'))?></td>
</tr>
</tbody>
</table>
