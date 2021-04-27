<?php
namespace sfmk;
/**
 * Database library
 */
class DBLib
{
    public $pdo;
    
    CONST TYPE_MAP=[
        'boolean' => \PDO::PARAM_INT,
        'integer' => \PDO::PARAM_INT,
        'double' => \PDO::PARAM_STR,
        'string' => \PDO::PARAM_STR,
        'NULL' => \PDO::PARAM_INT,
    ];

    public function __construct($pdo)
    {
        $this->pdo=$pdo;
    }

    /**
     * @param string $sql 要執行的 sql，可用 ? 配合 params 來 prepare
     * @param array $params 用來 prepare 的參數，會依據變數資料型態自動 bind
     * @param bool|int $mode
     *      1. false: 不執行，只回傳 PDOStatement
     *      2. true: 執行後回傳 PDOStatement
     *      3. PDO::FETCH_*: 執行後，回傳 fetchAll 的結果
     * @return false|mixed 依據 $mode 回傳資料型態，若有SQL錯誤回傳 false，params錯誤 throw error
     */
    public function execute($sql, $params=[], $mode=false)
    {
        if(($stmt=$this->pdo->prepare($sql))===false) {
            return false;
        }
        foreach($params as $idx=>$param) {
            $type=gettype($param);
            if(!isset(self::TYPE_MAP[$type])) {
                throw new Error('DBLib::prepareStmt 不支援類型 '.$type);
            }
            $stmt->bindValue($idx+1, $param, self::TYPE_MAP[$type]);
        }
        if($mode===false) { //不 execute
            return $stmt;
        }
        if($stmt->execute()===false) {
            return false;
        }
        if($mode===true) { //不 fetch
            return $stmt;
        }
        return $stmt->fetchAll($mode);
    }
}