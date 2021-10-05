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

    if(!isset($post['old_email'])) throw new Exception("old_email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['new_email'])) throw new Exception("new_email field is not set", E_FIELD_NOT_SET);
    if(!isset($post['template'])) throw new Exception("template field is not set", E_FIELD_NOT_SET);

    $old_email = trim($post['old_email']);
    if(!valid_email($old_email)) throw new Exception("old_email field is not valid", E_FIELD_INVALID);
    $new_email = trim($post['new_email']);
    if(!valid_email($new_email)) throw new Exception("new_email field is not valid", E_FIELD_INVALID);
    $template = trim($post['template']);
    if(!valid_str($template, 1, 4096)) throw new Exception("template field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();

      // check if account is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `accounts` WHERE `email` = ? LOCK IN SHARE MODE', array("s"), array($old_email));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Account doesn't exist", E_DOESNT_EXIST);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $stmt->close();

      // create token for email change
      $stmt = $mysql->query('SELECT create_token()');
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $token = $result->fetch_array()[0];
      $stmt->close();

      // store email token
      $stmt = $mysql->query('INSERT INTO `tokens`(`account_id`, `token`, `type`, `email`) VALUES (uuid_to_bin(?), token_to_bin(?), 3, ?)', array("s", "s", "s"), array($account_id, $token, $new_email));
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

    if(!mail($old_email, "Invitation", $message, implode("\r\n", $headers))) throw new Exception("Mail sending failed", E_MAIL_FAILED);

    send_response(SUCCESS, "");
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
