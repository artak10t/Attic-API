<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/request_password_token.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!USE_EMAIL_SUBSYSTEM) throw new Exception("Requires USE_EMAIL_SUBSYSTEM to be true", E_USE_EMAIL_SUBSYSTEM);

  if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);

  $email = trim($post['email']);
  if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id` FROM `accounts` WHERE `email` = ?', array("s"), array($email));
  $account_id = MySQL::stmt_result();
  $account_id = $account_id->fetch_assoc()['account_id'];

  if(!$account_id) send_response(SUCCESS, "");

  $prepend = "";
  if(isset($post['prepend']))
    $prepend = trim($post['prepend']);

  MySQL::query('SELECT create_token() AS `token`');
  $token = MySQL::stmt_result();
  $token = $token->fetch_assoc()['token'];

  MySQL::query('INSERT INTO `tokens`(`account_id`, `token_bin`, `token_type`)
                VALUES (?, token_to_bin(?), 2)', array("s", "s"), array($account_id, $token));

  MySQL::query('SELECT `fqdn` FROM `node`');
  $fqdn = MySQL::stmt_result();
  $fqdn = $fqdn->fetch_assoc()['fqdn'];

  MySQL::stmt_close();
  MySQL::commit();

  $headers[]  = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=iso-8859-1';
  $headers[] = 'From: Attic <admin@' . $fqdn . '>';
  $message = file_get_contents("$root/config/forms/email/account_password_change.html");
  $prepend = $prepend.$token;
  $message = str_replace("%link%", $prepend, $message);

  if(!mail($email, "Password Change", $message, implode("\r\n", $headers)))
    throw new Exception("Mail sending failed", E_MAIL_FAILED);

  send_response(SUCCESS, "");
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}
?>
