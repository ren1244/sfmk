<?php
$ct=new sfmk\Container();
$ct->add(sfmk\Response::class)
   ->setShared();
$ct->add(\PDO::class)
   ->addArgument(sprintf('%s:host=%s;dbname=%s', getenv('DB_DRIVER'), getenv('DB_HOST'), getenv('DB_NAME')))
   ->addArgument(getenv('DB_USER'))
   ->addArgument(getenv('DB_PASSWORD'))
   ->setShared();
$ct->add(sfmk\DBLib::class)
   ->addArgument(\PDO::class)
   ->setShared();
$ct->add(sfmk\Migration::class)
   ->addArgument(\PDO::class)
   ->addArgument(getenv('DB_NAME')) //dbname
   ->addArgument('migration') //table name
   ->addArgument(__DIR__ .'/migrations') //directory
   ->addArgument($ct) //container
   ->setShared();
