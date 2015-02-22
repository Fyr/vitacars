<?php
App::uses('Component', 'Controller');

class SiteEmailComponent extends Component {

	/**
	 * Parent controller
	 *
	 * @var object
	 */
	protected $_, $Email;

	public function initialize(Controller $controller) {
		$this->_ = $controller;
		
		$Email = new CakeEmail();
	}

	public function notify($to, $subj, $template, $data = null) {
		$Email->template($template, 'email')->viewVars( array('userId' => $userId, 'userName' => $userName, 'userMail' => $userMail, 'token' => $pass))
			->emailFormat('html')
			->from('admin@'.DOMAIN_NAME)
			->to($to)
			->subject($subj)
			->send();
	}
}