<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app['debug'] = true;

// Local Database connection
/*$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'db.options' => array(
	'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'dbname' => 'auditoria',
        'charset' => 'utf8'
	),
));*/

// Server Database connection
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
	   'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'user' => 'dev_usr',
        'password' => '4udit5uit3.',
        'dbname' => 'auditoria_dev',
        'charset' => 'utf8'
	),
));

// Registering cache service provider
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/cache/',
));

// Controllers for every section of the application
require_once "controllers/empresas.php";
require_once "controllers/usuarios.php";
require_once "controllers/cuestionarios.php";
require_once "controllers/reportes.php";
require_once "controllers/replicas.php";
require_once "controllers/dashboards.php";
require_once "controllers/emails.php";

$app['http_cache']->run();