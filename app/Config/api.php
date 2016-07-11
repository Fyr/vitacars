<?
Configure::write('ZzapApi', array(
	// 'url' => 'http://www.zzap.ru/webservice/test/datasharing1.asmx/',
	'url' => 'http://www.zzap.ru/webservice/datasharing.asmx/',
	'key' => 'EAAAAOInZ5vBwkgYdsvhjHvBppQYdUTeJ640oUJJzxCoE2vglu4v2Wm5xwo77ZCTSXvOHA==',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'zzap_api.log',
	'txtLog' => true,
	'dbLog' => false
));
/*
Configure::write('ElcatsApi', array(
	'url' => 'http://www.elcats.ru/',
	'cookie' => ROOT.DS.APP_DIR.DS.'config'.DS.'cookie_jar.txt',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'zzap_api.log'
));
*/
Configure::write('TechDocApi', array(
	'url' => 'http://pilot.api.iauto.by/get/',
	'key' => '6e8d6e800a22725dd2fc31b172a98401',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'techdoc_api.log',
	'txtLog' => true
));
Cache::config('techdoc', array(
	'engine' => 'DbTable',
	'storage' => 'cache_techdoc',
	'lock' => false,
	'serialize' => true,
));

Configure::write('AutoxpApi', array(
	'url' => 'http://app.autoxp.ru/pscomplex/catalog.aspx?salerind=917',
	'search_url' => 'http://app.autoxp.ru/support/catvinident.aspx?salerind=917&lavel=2',
	'cookies' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'autoxp_cookies.txt',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'autoxp_api.log',
	'txtLog' => true,
	'dbLog' => false
));
Cache::config('autoxp', array(
	'engine' => 'DbTable',
	'storage' => 'cache_autoxp',
	'lock' => false,
	'serialize' => true,
));

Configure::write('PartTradeApi', array(
	'url' => 'http://www.parttrade.ru/ws/services?wsdl',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'parttrade_api.log',
	'username' => 'giperzap',
	'password' => 'mogirus159',
	'txtLog' => true
));

Configure::write('ZapTradeApi', array(
	'url' => 'http://giperzap-by.zaptrade.ru/api/soap.php?wsdl',
	'log' => ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'zaptrade_api.log',
	'txtLog' => true,
	'username' => 'fyr.work@gmail.com',
	'password' => 'mogirus159'
));
