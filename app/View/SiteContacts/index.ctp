								<?=$article['Page']['body']?>
							</div>
						</div>
					</div>
					<div class="main_col_block">
						<?=$this->element('/SiteUI/page_title', array('pageTitle' => 'Отправить сообщение'))?>
						<div class="main_col_c">
							<div class="main_col_c_in">
<?
	echo $this->Form->create('Contact', array('class' => 'search_form', 'div' => 'search_form_row', 'inputDefaults' => array('div' => 'search_form_row')));
	echo $this->Form->input('Contact.username');
	echo $this->Form->input('Contact.email');
	echo $this->Form->input('Contact.body', array('type' => 'textarea', 'style' => 'background: #CBCBCB; width: 100%; border: 0 none;'));
	echo $this->Form->label(__('Spam protection'));
	echo $this->element('recaptcha');
	echo $this->Form->submit(__('Send'), array('class' => 'button orange_button', 'div' => 'search_form_row'));
	echo $this->Form->end();
?>