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

    if(!isset($post['folder_id'])) throw new Exception("folder_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
    $parent_id = null;
    if(isset($post['parent_id'])){
      $parent_id = trim($post['parent_id']);
      if(!valid_str($parent_id, 36, 36)) throw new Exception("parent_id field is not valid", E_FIELD_INVALID);
    }
    $folder_id = trim($post['folder_id']);
    if(!valid_str($folder_id, 36, 36)) throw new Exception("folder_id field is not valid", E_FIELD_INVALID);
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
      // check if folder_id is valid
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id` FROM `folders` WHERE `folder_id` = uuid_to_bin(?) FOR UPDATE', array("s"), array($folder_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Folder doesn't exist", E_DOESNT_EXIST);
      $account_id = $arr["account_id"];
      $stmt->close();

      // check if parent_id is valid, and owned by the same foreign account
      if (!is_null($parent_id)){
        $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id` FROM `folders` WHERE `folder_id` = uuid_to_bin(?) LOCK IN SHARE MODE', array("s"), array($parent_id));
        if ($mysql->deadlock()) continue;
        $result = $stmt->get_result();
        if (!$arr = $result->fetch_assoc()) throw new Exception("Parent folder doesn't exist", E_DOESNT_EXIST);
        if ($arr["account_id"] != $account_id) throw new Exception("Parent folder is owned by another account", E_UNAUTHORIZED);
        $stmt->close();
      }

      // move
      $stmt = $mysql->query('UPDATE `folders` SET `parent_id` = uuid_to_bin(?) WHERE `folder_id` = uuid_to_bin(?)',
                            array("s", "s"), array($parent_id, $folder_id));
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
