<?php
Configure::write('Dispatcher.filters', array(
	'AssetDispatcher',
	'CacheDispatcher'
));

App::uses('CakeLog', 'Log');
CakeLog::config('debug', array(
	'engine' => 'File',
	'types' => array('notice', 'info', 'debug'),
	'file' => 'debug',
));
CakeLog::config('error', array(
	'engine' => 'File',
	'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
	'file' => 'error',
));

Configure::write('Config.language', 'rus');

CakePlugin::loadAll();

// Values from google recaptcha account
define('RECAPTCHA_PUBLIC_KEY', '6Lezy-QSAAAAAJ_mJK5OTDYAvPEhU_l-EoBN7rxV');
define('RECAPTCHA_PRIVATE_KEY', '6Lezy-QSAAAAACCM1hh6ceRr445OYU_D_uA79UFZ');

Configure::write('Recaptcha.publicKey', RECAPTCHA_PUBLIC_KEY);
Configure::write('Recaptcha.privateKey', RECAPTCHA_PRIVATE_KEY);

define('DOMAIN_NAME', 'VitaCars.ru');
define('DOMAIN_TITLE', 'VitaCars.ru');

define('AUTH_ERROR', __('Invalid username or password, try again'));
define('TEST_ENV', isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '192.168.1.22');

define('EMAIL_ADMIN', 'fyr.work@gmail.com');
define('EMAIL_ADMIN_CC', 'fyr.work@gmail.com');

define('PATH_FILES_UPLOAD', $_SERVER['DOCUMENT_ROOT'].'/files/');
define('PATH_FILES_UPLOAD_BY', 'D:\Projects\agromotors.dev\wwwroot\app\webroot\files\\');
define('PATH_FILES_UPLOAD_RU', 'D:\Projects\agromotors.dev\wwwroot\app\webroot\files_ru\\');

define('SEPARATOR_DEICHARGE', '&nbsp;');
define('SEPARATOR_DECIMAL', ',');

Configure::write('Params', array(
	'color' => (TEST_ENV) ? 23 : 43,
	'A1' => 22,
	'A2' => (TEST_ENV) ? 19 : 27,
	'incomeY' => (TEST_ENV) ? 26 : 27,
	'outcomeY' => (TEST_ENV) ? 27 : 32,
));

Configure::write('Search', array(
	'detail_nums' => true
));

Configure::write('tmp_dir', ROOT.DS.APP_DIR.DS.'tmp'.DS);

Configure::write('import', array(
	'folder' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'csv'.DS,
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'import.log'
));

function fdebug($data, $logFile = 'tmp.log', $lAppend = true) {
	file_put_contents($logFile, mb_convert_encoding(print_r($data, true), 'cp1251', 'utf8'), ($lAppend) ? FILE_APPEND : null);
	return $data;
}