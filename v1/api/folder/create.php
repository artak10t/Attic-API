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

    $parent_id = null;
    if(isset($post['parent_id'])){
      $parent_id = trim($post['parent_id']);
      if(!valid_str($parent_id, 36, 36)) throw new Exception("parent_id field is not valid", E_FIELD_INVALID);
    }
    if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
    $name = trim($post['name']);
    if(!valid_restricted_str($name, 1, 255, FOLDER_NAME_RESTRICTIONS)) throw new Exception("name field is not valid", E_FIELD_INVALID);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if session_id is valid, and account is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `v_sessions` WHERE `session_id` = token_to_bin(?) LOCK IN SHARE MODE', array("s"), array($session_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $stmt->close();

      // check if parent_id is valid, and owned by the same account
      if (!is_null($parent_id)){
        $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id` FROM `folders` WHERE `folder_id` = uuid_to_bin(?) FOR UPDATE', array("s"), array($parent_id));
        if ($mysql->deadlock()) continue;
        $result = $stmt->get_result();
        if (!$arr = $result->fetch_assoc()) throw new Exception("Parent folder doesn't exist", E_DOESNT_EXIST);
        if ($arr["account_id"] != $account_id) throw new Exception("Parent folder is owned by another account", E_UNAUTHORIZED);
        $stmt->close();
      }

      // get UUID
      $stmt = $mysql->query('SELECT UUID()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $uuid = $result->fetch_array()[0];
      $stmt->close();

      // create folder
      $stmt = $mysql->query('INSERT INTO `folders`(`account_id`, `folder_id`, `parent_id`, `name`) VALUES (uuid_to_bin(?), uuid_to_bin(?), uuid_to_bin(?), ?)',
                            array("s", "s", "s", "s"), array($account_id, $uuid, $parent_id, $name));
      if ($mysql->deadlock()) continue;
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $data = array();
    $data["folder_id"] = $uuid;

    send_response(SUCCESS, "", $data);
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
