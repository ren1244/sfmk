<?php

class CreateExampleTable
{
    public function up(\PDO $pdo)
    {
        $stmt=$pdo->query('CREATE TABLE example(some_string VARCHAR(64))');
        if($stmt===false) {
            throw new Exception('error');
        }
    }

    public function down(\PDO $pdo)
    {
        $stmt=$pdo->query('DROP TABLE example');
        if($stmt===false) {
            throw new Exception('error');
        }
    }
}