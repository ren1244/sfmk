<?php
session_start();
require('vendor/autoload.php');
//載入環境變數
sfmk\EnvLoader::load('.env');
sfmk\Url::setRoot(getenv('URL_ROOT'));
sfmk\Response::setViewDir(__DIR__.'/app/view');
//註冊物件
require('service.php');
//路由
use sfmk\Route;
Route::setContainer($ct);
require('route.php');
sfmk\Response::error(404);