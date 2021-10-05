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

    if (!USE_EMAIL_SUBSYSTEM) throw new Exception("Email subsystem disabled", E_EMAIL_SUBSYSTEM_DISABLED);

    if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['template'])) throw new Exception("template field is not set", E_FIELD_NOT_SET);

    $email = trim($post['email']);
    if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);
    $template = trim($post['template']);
    if(!valid_str($template, 1, 4096)) throw new Exception("template field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();

      // check if account is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `accounts` WHERE `email` = ? LOCK IN SHARE MODE', array("s"), array($email));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("account doesn't exist", E_DOESNT_EXIST);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $stmt->close();

      // create password recovery token
      $stmt = $mysql->query('SELECT create_token()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $token = $result->fetch_array()[0];
      $stmt->close();

      // store invitation token
      $stmt = $mysql->query('INSERT INTO `tokens`(`account_id`, `token`, `type`) VALUES (uuid_to_bin(?), token_to_bin(?), 2)', array("s", "s"), array($account_id, $token));
      if ($mysql->deadlock()) continue;
      $stmt->close();

    } while ($mysql->deadlock());
    $mysql->commit();

    // Replace token in template
    $message = str_replace("%TOKEN%", $token, $template);
    // send Email
    $headers[]  = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';
    $headers[] = 'From: Attic <admin@' . FQDN . '>';

    if(!mail($email, "Invitation", $message, implode("\r\n", $headers))) throw new Exception("Mail sending failed", E_MAIL_FAILED);

    send_response(SUCCESS, "");
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
