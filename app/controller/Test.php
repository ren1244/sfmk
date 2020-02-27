<?php
namespace app\controller;

class Test
{
    public function index()
    {
        \sfmk\Response::view('hello');
    }
    
    public function install(\sfmk\Migration $migration)
    {
        while(($filename=$migration->up())!==false) {
            echo "<p>$filename</p>";
        }
    }
}