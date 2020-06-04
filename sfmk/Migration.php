<?php
namespace sfmk;

class Migration
{
    private $pdo;
    private $tableName;
    private $dirName;
    private $fnames;
    private $nFnames;
    private $currentIdx;
    private $container;
    public function __construct(\PDO $pdo, $dbName, $tableName, $dirName, $container)
    {
        $this->pdo=$pdo;
        $this->dbName=$dbName;
        $this->tableName=$tableName;
        $this->dirName=$dirName;
        $this->container=$container;
        //測試資料表是否存在，若不存在則建立
        $this->createTableIfNotExist();
        //掃描資料夾，寫入 $this->fnames
        $this->getFilesInDir();
        $this->lockTable();
        //確認檔案列表符合資料表中的內容，並回傳所在 index
        $this->currentIdx=$this->checkFilesMatchData();
    }
    
    public function __destruct()
    {
        $this->unLockTable();
    }
    
    private function createTableIfNotExist()
    {
        $sql='SELECT 1 FROM information_schema.tables
            WHERE table_schema=:dbname AND table_name=:tbname LIMIT 1';
        $stmt=$this->pdo->prepare($sql);
        $stmt->bindParam(':dbname', $this->dbName, \PDO::PARAM_STR);
        $stmt->bindParam(':tbname', $this->tableName, \PDO::PARAM_STR);
        if($stmt->execute()===false) {
            throw new \Exception('Migration::createTableIfNotExist error');
        }
        
        if($stmt->rowCount()<1) {
            $sql='CREATE TABLE '.$this->tableName.' (
                id INT PRIMARY KEY,
                file VARCHAR(64) UNIQUE KEY,
                status VARCHAR(16),
                create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=MyISAM CHARACTER SET=utf8mb4 COLLATE=utf8mb4_bin';
            $stmt=$this->pdo->query($sql);
            if($stmt===false){
                throw new \Exception('Migration::createTableIfNotExist: SQL ERROR: '.$this->pdo->errorInfo()[2]);
            }
        }
    }
    
    private function lockTable()
    {
        //$stmt=$this->pdo->query('LOCK TABLES '.$this->tableName.' WRITE');
        //if($stmt===false) {
        //    throw new \Exception('lock table faild');
        //}
    }
    
    private function unLockTable($errorInfo=false)
    {
        //$stmt=$this->pdo->query('UNLOCK TABLES');
        //if($stmt===false) {
        //    $errorInfo=$errorInfo===false?'lock table faild':$errorInfo.' & lock table faild';
        //}
        if($errorInfo!==false) {
            throw new \Exception($errorInfo);
        }
    }
    
    private function getFilesInDir()
    {
        $fList=scandir($this->dirName);
        $this->fnames=[];
        foreach($fList as $f) {
            if(
                is_dir($this->dirName .'/'.$f) ||
                preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_\w+\.php$/', $f)!==1
            ) {
                continue;
            }
            $this->fnames[]=$f;
        }
        sort($this->fnames);
        $this->nFnames=count($this->fnames);
    }
    
    private function checkFilesMatchData()
    {
        $sql='SELECT * FROM '.$this->tableName;
        $stmt=$this->pdo->query($sql);
        if($stmt===false) {
            $this->unLockTable('SQL ERROR: '.$sql);
        }
        $r=$stmt->fetchAll(\PDO::FETCH_ASSOC);
        $n=count($r);
        $m=count($this->fnames);
        if($m<$n) {
            throw new \Exception('#files less than #data in table');
        }
        for($i=0;$i<$n;++$i) {
            if($this->fnames[$i]!==$r[$i]['file'].'.php') {
                $this->unLockTable('checkFilesMatchData: not match');
            }
        }
        return $n;
    }
    
    public static function getClassName($fname)
    {
        if(preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(\w+)\.php$/', $fname, $mch)!==1) {
            return '';
        }
        $arr=explode('_', trim($mch[1], '_'));
        $arr=array_map(function($str){
            if($str==='') {
                return '';
            }
            return strtoupper($str[0]).substr($str, 1);
        }, $arr);
        return implode('', $arr);
    }
    
    private function setRecordStatus($fname, $status)
    {
        $stmt=$this->pdo->prepare(
            'SELECT id FROM '.$this->tableName.
            ' WHERE file=:file'
        );
        $stmt->bindValue(':file', $fname, \PDO::PARAM_STR);
        if($stmt->execute()===false) {
            $this->unLockTable('appendNewRecord error:'.$stmt->errorInfo[2]);
        }
        $r=$stmt->fetchAll(\PDO::FETCH_NUM);
        if(count($r)===0) {
            $id=$this->currentIdx;
            //插入新的一列
            $stmt=$this->pdo->prepare(
                'INSERT INTO '.$this->tableName.'(id, file, status)
                VALUES (:id, :file, :status)'
            );
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->bindValue(':file', $fname, \PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, \PDO::PARAM_STR);
            if($stmt->execute()===false) {
                $this->unLockTable('appendNewRecord error:'.$stmt->errorInfo[2]);
            }
        } else {
            //修改該列
            $id=$r[0];
            $stmt=$this->pdo->prepare(
                'UPDATE '.$this->tableName.
                ' SET status=:status WHERE id=:id'
            );
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, \PDO::PARAM_STR);
        }
    }
    
    private function loadFileAndRun($fname, $method)
    {
        if($method!=='up' && $method!=='down') {
            $this->unLockTable('loadFileAndRun error: bad $method.');
        }
        $className=self::getClassName($fname);
        $fn=substr($fname, 0, -4);
        try{
            require($this->dirName .'/'.$fname);
            $reflector=new \ReflectionClass($className);
            $obj=$reflector->newInstanceWithoutConstructor();
            $refMethod=$reflector->getMethod($method);
            $params=$refMethod->getParameters();
            $n=count($params);
            $argList=[];
            for($i=count($argList); $i<$n; ++$i) {
                $depName=$params[$i]->getClass()->getName();
                $argList[]=$this->container->get($depName);
            }
            call_user_func_array([$obj, $method], $argList);
        } catch(\Throwable $e) {
            echo $e;
            $this->unLockTable('loadFileAndRun error:'.$e);
        }
        if($method==='up') {
            $this->setRecordStatus($fn, 'done');
            ++$this->currentIdx;
        } elseif($method==='down') {
            $stmt=$this->pdo->prepare(
                'DELETE FROM '.$this->tableName.
                ' WHERE `file`=:file'
            );
            $stmt->bindValue(':file', $fn, \PDO::PARAM_STR);
            if($stmt->execute()===false) {
                $this->unLockTable('loadFileAndRun error:'.$stmt->errorInfo()[2]);
            }
            --$this->currentIdx;
        }
    }
    
    public function up()
    {
        if($this->currentIdx===$this->nFnames) {
            return false;
        }
        $fname=$this->fnames[$this->currentIdx];
        $this->loadFileAndRun($fname, 'up');
        return $fname;
    }
    
    public function down()
    {
        if($this->currentIdx<=0) {
            return false;
        }
        $fname=$this->fnames[$this->currentIdx-1];
        $this->loadFileAndRun($fname, 'down');
        return $fname;
    }
}