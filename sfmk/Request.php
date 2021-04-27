<?php

namespace sfmk;

class Request
{
    const FILTER_MAP=[
        'string' => FILTER_DEFAULT,
        'int' => FILTER_VALIDATE_INT,
        'bool' => FILTER_VALIDATE_BOOLEAN,
        'float' => FILTER_VALIDATE_FLOAT,
    ];
    private $classname;
    private $refClass;

    /**
     * 建構
     * 
     * @param string $calssname 過濾類別名稱，此類別定義了參數的過濾規則
     * @return void
     */
    public function __construct(string $classname)
    {
        $this->classname=$classname;
        $this->refClass=new \ReflectionClass($classname);
    }

    /**
     * 取得參數的資料 
     * 
     * @param int $inputType 類型 INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV
     * @param key $key 參數名稱
     * @param bool $optional 這個參數是否為可選的
     * @return mixed|null 過濾成功會回傳此參數的資料，否則交給「過濾類別」的 badRequest 方法處理
     *                    若無此參數時，會依據 $optional 參數回傳 null 或是交給「過濾類別」的 badRequest 方法處理
     */
    public function getData($inputType, $key, $optional=false)
    {
        //取得 type 
        if(!method_exists($this->classname, $key)) {
            $this->badRequest();
        }
        $refMethod=$this->refClass->getMethod($key);
        $refParams=$refMethod->getParameters();
        $type=$refParams[0]->getType();
        $type=$type!==null?$type->getName():'string';
        //依據類型，過濾參數
        $val=filter_input($inputType, $key, self::FILTER_MAP[$type]);
        if($val===null && !$optional) { //required but not set
            $this->badRequest();
        }
        if($type==='bool') {
            if($val===null) {
                return null;
            }
            $val=filter_input($inputType, $key, self::FILTER_MAP[$type], FILTER_NULL_ON_FAILURE);
        }
        if(
            ($type==='bool'?$val===null:$val===false) ||
            ($val!==null && $refMethod->invoke(null, $val)===false)
        ) { //filter failure
            $this->badRequest();
        }
        return $val;
    }

    /**
     * 取得多個參數，以 [ker=>val,...] 方式回傳
     * 
     * @param int $inputType 同 getData
     * @param array $params 這個陣列有兩個 key，分別為 'required' 與 'optional'
     *              'required' 與 'optional' 的值為一個陣列
     *              此陣列列出了要被取得參數的名稱
     * @return array [參數名稱=>值] 的關聯陣列
     */
    public function getArray($inputType, $params=[])
    {
        $result=[];
        if(isset($params['required'])) {
            foreach($params['required'] as $key) {
                $result[$key]=$this->getData($inputType, $key, false);
            }
        }
        if(isset($params['optional'])) {
            foreach($params['optional'] as $key) {
                $result[$key]=$this->getData($inputType, $key, true);
            }
        }
        return $result;
    }

    /**
     * 當錯誤發生時的處理
     */
    private function badRequest()
    {
        if(method_exists($this->classname, 'onBadRequest')) {
            call_user_func([$this->classname, 'onBadRequest']);
        } else {
            http_response_code(400);
            echo 'Bad Request';
            exit();
        }
    }

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