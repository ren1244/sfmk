<?php
namespace sfmk;
class EnvLoader
{
    public static function load($envFile)
    {
        $fp=fopen($envFile, "r");
        while(($line=fgets($fp))!==false) {
            if(($pos=strpos($line, '//'))!==false) {
                $line=substr($line, 0, $pos);
            }
            $line=trim($line);
            if(($pos=strpos($line, '='))!==false) {
                putenv($line);
            }
        }
    }
}
