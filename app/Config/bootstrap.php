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

Configure::write('domain', array(
	'url' => 'vitacars.dev',
	'title' => 'VitaCars.dev'
));

// Values from google recaptcha account
define('RECAPTCHA_PUBLIC_KEY', '6Lezy-QSAAAAAJ_mJK5OTDYAvPEhU_l-EoBN7rxV');
define('RECAPTCHA_PRIVATE_KEY', '6Lezy-QSAAAAACCM1hh6ceRr445OYU_D_uA79UFZ');

Configure::write('Recaptcha.publicKey', RECAPTCHA_PUBLIC_KEY);
Configure::write('Recaptcha.privateKey', RECAPTCHA_PRIVATE_KEY);

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
	'color' => 43,
	'A1' => 22,
	'A2' => 27,
	'incomeY' => 27,
	'outcomeY' => 32,
	'crossNumber' => 60,
	'motor' => 6,
	'motorTS' => 34,
	'skladSNG' => 49,
	'skladOrig' => 72,
	'skladEur' => 71,
	'x_info' => 9,
	'discountPrice' => 18,
	'discount' => 82,
	'discountComment' => 84,
	'fkColor' => array(
		'fk-green' => array(18, 31, 77)
	)
));

Configure::write('Search', array(
	'detail_nums' => true
));

Configure::write('tmp_dir', ROOT.DS.APP_DIR.DS.'tmp'.DS);

Configure::write('import', array(
	'folder' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'csv'.DS,
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'import.log',
	'db_log' => false
));

Configure::write('domains', array('by', 'ru', 'ua', 'bg'));

Cache::config('tasks', array(
	'engine' => 'File',
	'duration' => '+999 days',
	'probability' => 100,
	'prefix' => 'tasks_',
	'serialize' => true,
	'mask' => 0664,
));


require_once('api.php');
require_once('assert.php');

function fdebug($data, $logFile = 'tmp.log', $lAppend = true) {
	file_put_contents($logFile, mb_convert_encoding(print_r($data, true), 'cp1251', 'utf8'), ($lAppend) ? FILE_APPEND : null);
	return $data;
}




