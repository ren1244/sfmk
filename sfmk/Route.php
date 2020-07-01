<?php

namespace sfmk;

use sfmk\Container;

class Route
{
    private static $method=false;
    private static $uri=false;
    private static $container=false;
    private static $filters=[];
    private static $filterStatus=true;
    
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
    
    /** 
     * 註冊 filter
     *
     * @param string $filterKey 用於辨識 filter 的名稱
     * @param array|string|function $filterContent 定義過濾的方法
     * @return void
     */
    public static function filterRegistry($filterKey, $filterContent)
    {
        if(isset(self::$filters[$filterKey])) {
            throw new \Exception('duplicate filterKey');
        }
        self::$filters[$filterKey]=$filterContent;
    }
    
    public static function useFilter($filterKey='none')
    {
        if($filterKey==='none'){
            self::$filterStatus=true;
            return;
        }
        if(!isset(self::$filters[$filterKey])) {
            throw new \Exception('No such filter');
        }
        $filterContent=&self::$filters[$filterKey];
        $fType=gettype($filterContent);
        if($fType==='boolean') {
            self::$filterStatus=$filterContent;
            return;
        } elseif($fType==='array') {
            foreach($filterContent as $f) {
                if(!self::useFilter($f)) {
                    self::$filters[$filterKey]=false;
                    self::$filterStatus=$false;
                    return;
                }
            }
            self::$filters[$filterKey]=true;
            self::$filterStatus=$true;
            return;
        } else {
            self::$filterStatus=
            self::$filters[$filterKey]=
            self::executeIoCFunction($filterContent);
        }
    }
    
    private static function testToRun($testUri, $cbk)
    {
        if(!self::$filterStatus) {
            return;
        }
        $testUri=Url::getPathArray($testUri);
        $uri=self::getUri();
        //比對 $testUri 與 self::$uri 是否符合，是的話執行 cbk
        //之後加上路由參數
        $n=count($uri);
        $m=count($testUri);
        if($n!==$m) {
            return;
        }
        $routeParams=[];
        for($i=0;$i<$n;++$i){
            $len=strlen($testUri[$i]);
            if($len>=2 && $testUri[$i][0]==='{' && $testUri[$i][$len-1]==='}') {
                $routeParams[]=$uri[$i];
                continue;
            }
            if($testUri[$i]!==$uri[$i]) {
                break;
            }
        }
        if($i===$n) {
            self::executeIoCFunction($cbk, $routeParams, '\\app\\controller\\');
            exit();
        }
    }
    
    /** 
     * 執行某 'class@方法' or Closure
     *
     * @param sting|function $cbk 要被執行的函式或方法
     * @param array $routeParam 其他主動添加的參數
     * @return void
     */
    private static function executeIoCFunction($cbk, $otherParam=[], $spacename='')
    {
        
        if(gettype($cbk)==='string') { //視為 calssName@method
            $cbk=explode('@', $spacename.$cbk);
            $reflector=new \ReflectionClass($cbk[0]);
            $refConstructor=$reflector->getConstructor();
            //建立物件
            if(is_null($refConstructor)) {
                $objInstance=$reflector->newInstanceWithoutConstructor();
            } else {
                $argList=self::getReflectArgList($refConstructor);
                $objInstance=$reflector->newInstanceArgs($argList);
            }
            //呼叫 method
            $refMethod=$reflector->getMethod($cbk[1]);
            $argList=self::getReflectArgList($refMethod, $otherParam);
            return call_user_func_array([$objInstance, $cbk[1]], $argList);
        } else { // Closure Function
            $reflect=new \ReflectionFunction($cbk);
            $argList=self::getReflectArgList($reflect, $otherParam);
            return call_user_func_array($cbk, $argList);
        }
    }
    
    private static function getReflectArgList(&$reflector, $appendParams=[])
    {
        $params=$reflector->getParameters();
        $n=count($params);
        for($i=count($appendParams); $i<$n; ++$i) {
            $depName=$params[$i]->getClass()->getName();
            $appendParams[]=self::$container->get($depName);
        }
        return $appendParams;
    }
    
    private static function getMethod()
    {
        return self::getVar('method', 'REQUEST_METHOD');
    }
    
    private static function getUri()
    {
        return self::getVar('uri', 'REQUEST_URI', function($m){
            //轉換為相對根目錄
            $m=strtok($m ,'#');
            $m=strtok($m ,'?');
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