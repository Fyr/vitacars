<?php
Router::parseExtensions('html', 'json');
// Router::connect('/', array('controller' => 'SitePages', 'action' => 'home'));
// Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
Router::connect('/', array('controller' => 'Admin', 'action' => 'index'));

CakePlugin::routes();

require CAKE.'Config'.DS.'routes.php';
