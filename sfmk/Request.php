<?php

namespace sfmk;

class Request
{
    /**
     * 類似 filter_input_array 取得過濾後的輸入值
     * https://www.php.net/manual/en/function.filter-input-array.php
     * 
     * @param int $type One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
     * @param array|int $required 必要欄位，參考 filter_input_array 的 $options
     * @param array|int $optional 選擇性欄位，參考 filter_input_array 的 $options
     * @return array|false 成功回傳結果的關聯陣列
     *                     必有 required 跟 optional 的 key 值
     *                     只是 optional 的 value 可能是 null
     *                     失敗回傳 false
     */
    public static function filterInputs($type, $required=false, $optional=false)
    {
        if($required) {
            $inputs=filter_input_array($type, $required);
            if(is_null($inputs) || $inputs===false) {
                return false;
            }
            foreach($required as $k=>$v) {
                if(
                    !isset($inputs[$k]) ||
                    is_null($inputs[$k]) ||
                    ($inputs[$k]===false && (is_array($v)?$v['filter']:$v)!==FILTER_VALIDATE_BOOLEAN)
                ) {
                    return false;
                }
            }
        } else {
            $inputs=[];
        }
        if($optional) {
            $inputs2=filter_input_array($type, $optional);
            if(is_null($inputs2) || $inputs2===false) {
                return false;
            }
            foreach($optional as $k=>$v) {
                if(!isset($inputs2[$k])) {
                    $inputs2[$k]=null;
                } elseif(
                    $inputs2[$k]===false && $v!==FILTER_VALIDATE_BOOLEAN
                ) {
                    return false;
                }
            }
        } else {
            $inputs2=[];
        }
        return array_merge($inputs, $inputs2);
    }
}