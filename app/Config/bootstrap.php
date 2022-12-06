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
	'url' => 'vitacars.loc',
	'title' => 'VitaCars.loc'
));

// Values from google recaptcha account
define('RECAPTCHA_PUBLIC_KEY', '6Lezy-QSAAAAAJ_mJK5OTDYAvPEhU_l-EoBN7rxV');
define('RECAPTCHA_PRIVATE_KEY', '6Lezy-QSAAAAACCM1hh6ceRr445OYU_D_uA79UFZ');

Configure::write('Recaptcha.publicKey', RECAPTCHA_PUBLIC_KEY);
Configure::write('Recaptcha.privateKey', RECAPTCHA_PRIVATE_KEY);

define('AUTH_ERROR', __('Invalid username or password, try again'));
define('TEST_ENV', isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1');

define('EMAIL_ADMIN', 'fyr.work@gmail.com');
define('EMAIL_ADMIN_CC', 'fyr.work@gmail.com');

define('PATH_FILES_UPLOAD', WWW_ROOT.'files'.DS);

define('SEPARATOR_DEICHARGE', '&nbsp;');
define('SEPARATOR_DECIMAL', ',');

Configure::write('Params', array(
	'color' => 43,
	'A1' => 28,
	'A2' => 87,
	'incomeY' => 50,
	'outcomeY' => 51,
	'crossNumber' => 60,
	'motor' => 6,
	'motorTS' => 34,
	'sklad_fks' => array(28,87,30,70,25,96,102,103,105,107),
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
	'folder' => ROOT . DS . APP_DIR . DS . 'tmp' . DS . 'import' . DS,
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




