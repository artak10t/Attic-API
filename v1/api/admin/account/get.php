<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");
include_once("$root/v1/utils/http.php");
include_once("$root/v1/utils/errors.php");
include_once("$root/v1/utils/validations.php");

$mysql = new MYSQL();
try{
  try{
    if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);
    $post = decode_post();

    if(!isset($post['foreign_account_id'])) throw new Exception("foreign_account_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

    $foreign_account_id = trim($post['foreign_account_id']);
    if(!valid_str($foreign_account_id, 36, 36)) throw new Exception("foreign_account_id field is not valid", E_FIELD_INVALID);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if session_id is valid, user is admin and account is enabled
      $stmt = $mysql->query('SELECT `admin`, `disabled`, `reason` FROM `v_sessions` WHERE `session_id` = token_to_bin(?)', array("s"), array($session_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
      if (!$arr["admin"]) throw new Exception("Not authorized", E_UNAUTHORIZED);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // get target account
      $stmt = $mysql->query('SELECT `name`, `email`, `admin`, `disabled`, `reason`, `max_space`, `current_space` FROM `accounts` WHERE `account_id` = uuid_to_bin(?)',  array("s"), array($foreign_account_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Foreign account doesn't exist", E_DOESNT_EXIST);
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $data = array();
    $data["account_id"] = $foreign_account_id;
    $data["name"] = $arr["name"];
    $data["email"] = $arr["email"];
    $data["admin"] = $arr["admin"];
    $data["disabled"] = $arr["disabled"];
    $data["reason"] = $arr["reason"];
    $data["max_space"] = $arr["max_space"];
    $data["current_space"] = $arr["current_space"];

    send_response(SUCCESS, "", $data);
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
