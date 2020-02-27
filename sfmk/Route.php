<?php

namespace sfmk;

use sfmk\Container;

class Route
{
    private static $method=false;
    private static $uri=false;
    private static $container=false;
    
    public static function setContainer(Container $container)
    {
        self::$container=$container;
    }

    public static function get($testUri, $cbk)
    {
        if(self::getMethod()!=='GET') {
            return;
        }
        self::testToRun($testUri, $cbk);
    }

    public static function post($testUri, $cbk)
    {
        if(self::getMethod()!=='POST') {
            return;
        }
        self::testToRun($testUri, $cbk);
    }
    
    private static function testToRun($testUri, $cbk)
    {
        $testUri=Url::getPathArray($testUri);
        $uri=self::getUri();
        //比對 $testUri 與 self::$uri 是否符合，是的話執行 cbk
        //之後加上路由參數
        $n=count($uri);
        $m=count($testUri);
        if($n!==$m) {
            return;
        }
        for($i=0;$i<$n;++$i){
            if($testUri[$i]!==$uri[$i]) {
                break;
            }
        }
        if($i===$n) {
            self::executeRoute($cbk);
        }
    }
    
    public static function executeController($controllerName, $methodName, $routeParam=[])
    {
        $className='\\app\\controller\\'.$controllerName;
        $reflector=new \ReflectionClass($className);
        $refConstructor=$reflector->getConstructor();
        //建立 controller 物件
        if(is_null($refConstructor)) {
            $controller=$reflector->newInstanceWithoutConstructor();
        } else {
            $params=$refConstructor->getParameters();
            $argList=[];
            foreach($params as $param){
                $depName=$param->getType()->getName();
                $argList[]=self::$container->get($depName);
            }
            $controller=$reflector->newInstanceArgs($argList);
        }
        //呼叫 method
        $refMethod=$reflector->getMethod($methodName);
        $params=$refMethod->getParameters();
        $argList=$routeParam;
        $n=count($params);
        for($i=count($argList); $i<$n; ++$i) {
            $depName=$params[$i]->getType()->getName();
            $argList[]=self::$container->get($depName);
        }
        call_user_func_array([$controller, $methodName], $argList);
    }
    
    private static function executeRoute($cbk, $routeParam=[])
    {
        if(gettype($cbk)==='string') {
            $pos=strpos($cbk, '@');
            if($pos===false) {
                $controller=$cbk;
                $method='index';
            } else {
                $controller=substr($cbk, 0, $pos);
                $method=substr($cbk, $pos+1);
            }
            self::executeController($controller, $method, $routeParam);
        } else {
            $cbk();
        }
        exit();
    }
    
    private static function getMethod()
    {
        return self::getVar('method', 'REQUEST_METHOD');
    }
    
    private static function getUri()
    {
        return self::getVar('uri', 'REQUEST_URI', function($m){
            //轉換為相對根目錄
            $arr=Url::getPathArray($m);
            $n=min(count(Url::$root), count($arr));
            for($i=0; $i<$n; ++$i) {
                if($arr[$i]!==Url::$root[$i]) {
                    break;
                }
            }
            return array_slice($arr, $i);
        });
    }
    
    private static function getVar($varName, $sName, $cbk=false)
    {
        if(self::$$varName!==false) {
            return self::$$varName;
        }
        $m=filter_input(INPUT_SERVER, $sName);
        if(is_null($m)) {
            $m=false;
        }
        if($cbk) {
            $m=call_user_func($cbk, $m);
        }
        self::$$varName=$m;
        return $m;
    }
}