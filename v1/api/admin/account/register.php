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

    if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
    if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

    $name = trim($post['name']);
    if(!valid_restricted_str($name, 1, 64, ACCOUNT_NAME_RESTRICTIONS)) throw new Exception("name field is not valid", E_FIELD_INVALID);
    $email = trim($post['email']);
    if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);
    $password = trim($post['password']);
    if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);

    $admin = 0;
    if(isset($post['admin'])) $admin = trim($post['admin']);
    if(!valid_int($admin, 0, 1)) throw new Exception("admin field is not valid", E_FIELD_INVALID);
    $disabled = 0;
    if(isset($post['disabled'])) $disabled = trim($post['disabled']);
    if(!valid_int($disabled, 0, 1)) throw new Exception("disabled field is not valid", E_FIELD_INVALID);
    $reason = "";
    if(isset($post['reason'])) $reason = $post['reason']; // do not trim
    if(!valid_str($reason, 0, 500)) throw new Exception("reason field is not valid", E_FIELD_INVALID);
    $max_space = -1;
    if(isset($post['max_space'])) $max_space = trim($post['max_space']);
    if(!valid_int($max_space, -1)) throw new Exception("max_space field is not valid", E_FIELD_INVALID);

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
      // get UUIDs
      $stmt = $mysql->query('SELECT UUID(), UUID()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $arr = $result->fetch_array();
      $uuid = $arr[0];
      $f_uuid = $arr[1];
      $stmt->close();

      // register account
      if ($max_space < 0){
        $stmt = $mysql->query('INSERT INTO `accounts`(`account_id`, `name`, `email`, `password`, `admin`, `disabled`, `reason`, `max_space`) '.
                              'VALUES (uuid_to_bin(?), ?, ?, encrypt_string(?), ?, ?, ?, (SELECT `max_space` FROM `config`))',
                              array("s", "s", "s", "s", "i", "i", "s"), array($uuid, $name, $email, $password, $admin, $disabled, $reason));
      } else {
        $stmt = $mysql->query('INSERT INTO `accounts`(`account_id`, `name`, `email`, `password`, `admin`, `disabled`, `reason`, `max_space`) '.
                              'VALUES (uuid_to_bin(?), ?, ?, encrypt_string(?), ?, ?, ?, ?)',
                              array("s", "s", "s", "s", "i", "i", "s", "i"), array($uuid, $name, $email, $password, $admin, $disabled, $reason, $max_space));
      }
      if ($mysql->deadlock()) continue;
      $stmt->close();

      // create one new folder
      $stmt = $mysql->query('INSERT INTO `folders`(`account_id`, `parent_id`, `folder_id`, `name`) VALUES (uuid_to_bin(?), NULL, uuid_to_bin(?), ?)',
                            array("s", "s", "s"), array($uuid, $f_uuid, "New Folder"));
      if ($mysql->deadlock()) continue;
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $data = array();
    $data["account_id"] = $uuid;

    send_response(SUCCESS, "", $data);
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
