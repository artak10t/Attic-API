<?php

$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");

try{
  $mysql = new MYSQL(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);
  try{
    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();

      $stmt = $mysql->query("SELECT * FROM `accounts`");
      if ($mysql->deadlock())
        continue;
      $result = $stmt->get_result();
      while ($arr = $result->fetch_assoc()){
        print $arr["name"]." ";
      }

      $stmt = $mysql->query("UPDATE `accounts` SET `name` = 'admin' WHERE `name` = 'admin'");
      if ($mysql->deadlock())
        continue;
      print "success";

    } while ($mysql->deadlock());

    $mysql->commit();

  }catch(Exception $e){
    print $e->getCode().$e->getMessage();
  }
}finally{
  $mysql->rollback();
  $mysql->disconnect();
}

?>
