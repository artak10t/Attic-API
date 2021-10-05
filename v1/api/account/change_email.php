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

    $token = trim($post['token']);
    if(!valid_str($token, 64, 64)) throw new Exception("token field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if token is valid, target account exists and is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `email`, `disabled`, `reason` FROM `v_tokens` WHERE `token` = token_to_bin(?) AND `type` = 3 LOCK IN SHARE MODE',
                             array("s"), array($token));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid or expired token", E_INVALID_TOKEN);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $email = $arr["email"];
      $stmt->close();

      // change email
      $stmt = $mysql->query('UPDATE `accounts` SET `email` = ? WHERE `account_id` = uuid_to_bin(?)', array("s", "s"), array($email, $account_id));
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
