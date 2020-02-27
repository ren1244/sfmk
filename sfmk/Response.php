<?php
namespace sfmk;

class Response
{
    private static $viewDir;
    private static $httpStatus=[
        '200' => 'OK',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '500' => 'Internal Server Error',
        '503' => 'Service Unavailable',
    ];
    public static function setViewDir($viewDir)
    {
        self::$viewDir=$viewDir;
    }
    
    public static function view($sfmk_view_name, $sfmk_view_params=[])
    {
        foreach($sfmk_view_params as $sfmk_view_key=>$sfmk_view_val){
            $$sfmk_view_key=$sfmk_view_val;
        }
        require(self::$viewDir.'/'.$sfmk_view_name.'.php');
    }
    
    public static function error($code, $str=false)
    {
        http_response_code($code);
        if($str===false) {
            $str=isset(self::$httpStatus[$code])?self::$httpStatus[$code]:'';
        }
        exit($str);
    }
    
    public static function success($str='Ok')
    {
        http_response_code(200);
        exit($str);
    }
    
    public static function redirect($url)
    {
        header('Location: '.Url::getFullUrl($url));
        exit();
    }
}