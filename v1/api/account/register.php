<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");
include_once("$root/v1/utils/http.php");
include_once("$root/v1/utils/errors.php");
include_once("$root/v1/utils/consts.php");
include_once("$root/v1/utils/consts.php");
include_once("$root/v1/utils/validations.php");

$mysql = new MYSQL();
try{
  try{
    if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);
    $post = decode_post();

    if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
    if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);
    if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);

    $name = trim($post['name']);
    if(!valid_restricted_str($name, 1, 64, ACCOUNT_NAME_RESTRICTIONS)) throw new Exception("name field is not valid", E_FIELD_INVALID);
    $password = trim($post['password']);
    if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);
    $token = trim($post['token']);
    if(!valid_str($token, 64, 64)) throw new Exception("token field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if token is valid
      $stmt = $mysql->query('SELECT `email` FROM `v_tokens` WHERE `type` = 1 AND `token` = token_to_bin(?)', array("s"), array($token));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid or expired token", E_INVALID_TOKEN);
      $email = $arr["email"];
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // get UUID
      $stmt = $mysql->query('SELECT UUID(), UUID()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $arr = $result->fetch_array();
      $uuid = $arr[0];
      $f_uuid = $arr[1];
      $stmt->close();

      // Register account
      $stmt = $mysql->query('INSERT INTO `accounts`(`account_id`, `name`, `email`, `password`, `max_space`) '.
                            'VALUES (uuid_to_bin(?), ?, ?, encrypt_string(?), (SELECT `max_space` FROM `config`))',
                            array("s","s", "s", "s"), array($uuid, $name, $email, $password));
      if ($mysql->deadlock()) continue;
      $stmt->close();

      // create one new folder
      $stmt = $mysql->query('INSERT INTO `folders`(`account_id`, `parent_id`, `folder_id`, `name`) VALUES (uuid_to_bin(?), NULL, uuid_to_bin(?), ?)',
                            array("s", "s", "s"), array($uuid, $f_uuid, "New Folder"));
      if ($mysql->deadlock()) continue;
      $stmt->close();

      // delete token
      $stmt = $mysql->query('DELETE FROM `tokens` WHERE `token` = token_to_bin(?)', array("s"), array($token));
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
