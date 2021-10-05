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

    if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);

    $email = trim($post['email']);
    if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);
    $password = trim($post['password']);
    if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);
    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();

      // get account id
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `accounts` WHERE `email` = ? AND `password` = encrypt_string(?) LOCK IN SHARE MODE', array("s", "s"), array($email, $password));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if(!$arr = $result->fetch_assoc()) throw new Exception("Invalid credentials", E_INVALID_CREDENTIALS);
      $account_id = $arr["account_id"];
      $stmt->close();
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);

      // create token
      $stmt = $mysql->query('SELECT create_token()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $token = $result->fetch_array()[0];
      $stmt->close();

      // update sessions
      $stmt = $mysql->query('INSERT INTO `sessions` (`session_id`, `account_id`) VALUES (token_to_bin(?), uuid_to_bin(?))', array("s", "s"), array($token, $account_id));
      if ($mysql->deadlock()) continue;
      $stmt->close();

    } while ($mysql->deadlock());

    $mysql->commit();

    $data = array();
    $data["session_id"] = $token;

    send_response(SUCCESS, "", $data);
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
