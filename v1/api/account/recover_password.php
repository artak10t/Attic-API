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

    if (!USE_EMAIL_SUBSYSTEM) throw new Exception("Email subsystem disabled.", E_EMAIL_SUBSYSTEM_DISABLED);

    if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);
    if(!isset($post['new_password'])) throw new Exception("new_password field is not set", E_FIELD_NOT_SET);

    $token = trim($post['token']);
    if(!valid_str($token, 64, 64)) throw new Exception("token field is not valid", E_FIELD_INVALID);
    $new_password = trim($post['new_password']);
    if(!valid_password($new_password, 6, 32)) throw new Exception("new_password field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if token is valid, account exists and is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `v_tokens` WHERE `token` = token_to_bin(?) AND `type` = 2 LOCK IN SHARE MODE', array("s"), array($token));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid or expired token", E_INVALID_TOKEN);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $stmt->close();

      // change password
      $stmt = $mysql->query('UPDATE `accounts` SET `password` = encrypt_string(?) WHERE `account_id` = uuid_to_bin(?)', array("s", "s"), array($new_password, $account_id));
      if ($mysql->deadlock()) continue;
      $stmt->close();

      // remove token
      $stmt = $mysql->query('DELETE FROM `tokens` WHERE `token` = token_to_bin(?)', array("s"), array($token));
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
