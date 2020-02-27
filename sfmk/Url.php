<?php
namespace sfmk;

class Url
{
    public static $root=[];
    /** 
     * 設定根目錄
     *
     * @param strint $url 根目錄，例如: "/site1"
     * @return void
     */
    public static function setRoot($url)
    {
        self::$root=self::getPathArray($url);
    }
    
    public static function getPathArray($url)
    {
        $url=trim($url, '/');
        return $url===''?[]:explode('/', $url);
    }
    
    public static function getFullUrl($url)
    {
        $url=self::getPathArray($url);
        $url=array_merge(self::$root, $url);
        return '/'.(count($url)>0?implode('/', $url):'');
    }
    
    public static function getResource($theFile)
    {
        return '/'.implode('/', self::$root).'/public/'.$theFile;
    }
}