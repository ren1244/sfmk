<?php
require('vendor/autoload.php');
sfmk\EnvLoader::load('.env');
date_default_timezone_set(getenv('TIMEZONE'));

if(
    $argc===3 &&
    $argv[1]==='make:migration' &&
    preg_match('/^\w+$/', $argv[2])===1
) {
    $fname=date('Y_m_d_His_').$argv[2].'.php';
    $className=sfmk\Migration::getClassName($fname);
    $template=<<<EOD
<?php

class $className
{
    public function up()
    {
        
    }

    public function down()
    {
        
    }
}
EOD;
    file_put_contents(__DIR__ .'/migrations/'.$fname, $template);
} elseif(
    $argc===3 &&
    $argv[1]==='make:controller' &&
    preg_match('/^\w+$/', $argv[2])===1
) {
    $fname=$argv[2].'.php';
    $className=$argv[2];
    $template=<<<EOD
<?php
namespace app\controller;

class $className
{
    public function index()
    {
        
    }
}
EOD;
    file_put_contents(__DIR__ .'/app/controller/'.$fname, $template);
}