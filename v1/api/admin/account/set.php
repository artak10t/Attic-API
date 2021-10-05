<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");
include_once("$root/v1/utils/http.php");
include_once("$root/v1/utils/errors.php");
include_once("$root/v1/utils/consts.php");
include_once("$root/v1/utils/validations.php");

$mysql = new MYSQL();
try{
  try{
    if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);
    $post = decode_post();

    if(!isset($post['foreign_account_id'])) throw new Exception("foreign_account_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
    if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['admin'])) throw new Exception("admin field is not set", E_FIELD_NOT_SET);
    if(!isset($post['disabled'])) throw new Exception("disabled field is not set", E_FIELD_NOT_SET);
    if(!isset($post['reason'])) throw new Exception("reason field is not set", E_FIELD_NOT_SET);
    if(!isset($post['max_space'])) throw new Exception("max_space field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

    $foreign_account_id = trim($post['foreign_account_id']);
    if(!valid_str($foreign_account_id, 36, 36)) throw new Exception("foreign_account_id field is not valid", E_FIELD_INVALID);
    $name = trim($post['name']);
    if(!valid_restricted_str($name, 1, 64, ACCOUNT_NAME_RESTRICTIONS)) throw new Exception("name field is not valid", E_FIELD_INVALID);
    $email = trim($post['email']);
    if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);
    $admin = trim($post['admin']);
    if(!valid_int($admin, 0, 1)) throw new Exception("admin field is not valid", E_FIELD_INVALID);
    $disabled = trim($post['disabled']);
    if(!valid_int($disabled, 0, 1)) throw new Exception("disabled field is not valid", E_FIELD_INVALID);
    $reason = $post['reason']; // do not trim
    if(!valid_str($reason, 0, 500)) throw new Exception("reason field is not valid", E_FIELD_INVALID);
    $max_space = $post['max_space'];
    if(!valid_int($max_space)) throw new Exception("max_space field is not valid", E_FIELD_INVALID);
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
      // chack if target account exists
      $stmt = $mysql->query('SELECT 1 FROM `accounts` WHERE `account_id` = uuid_to_bin(?) FOR UPDATE', array("s"), array($foreign_account_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Foreign account doesn't exist", E_DOESNT_EXIST);
      $stmt->close();

      // set target account
      $stmt = $mysql->query('UPDATE `accounts` SET `name` = ?, `email` = ?, `admin` = ?, `disabled` = ?, `reason` = ?, `max_space` = ? WHERE `account_id` = uuid_to_bin(?)',
                            array("s", "s", "i", "i", "s", "i", "s"), array($name, $email, $admin, $disabled, $reason, $max_space, $foreign_account_id));
      if ($mysql->deadlock()) continue;
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    send_response(SUCCESS, "");
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
