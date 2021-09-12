<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/request_activation_token.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

  $session_id = trim($post['session_id']);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `enabled`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  $prepend = "";
  if(isset($post['prepend']))
    $prepend = trim($post['prepend']);

  MySQL::query('SELECT create_token() AS `token`');
  $token = MySQL::stmt_result();
  $token = $token->fetch_assoc()['token'];

  MySQL::query('INSERT INTO `tokens`(`account_id`, `token_bin`, `token_type`)
                VALUES ((SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)), token_to_bin(?), 0)', array("s", "s"), array($session_id, $token));

  MySQL::query('SELECT `email` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $email = MySQL::stmt_result();
  $email = $email->fetch_assoc()['email'];

  MySQL::query('SELECT `fqdn` FROM `node`');
  $fqdn = MySQL::stmt_result();
  $fqdn = $fqdn->fetch_assoc()['fqdn'];

  MySQL::stmt_close();
  MySQL::commit();

  if(!USE_EMAIL_SUBSYSTEM)
  {
    $data = new stdClass();
    $data->token = $token;
    send_response(SUCCESS, "", $data);
  }

  $headers[]  = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=iso-8859-1';
  $headers[] = 'From: Attic <admin@' . $fqdn . '>';
  $message = file_get_contents("$root/config/forms/email/account_activate.html");
  $prepend = $prepend.$token;
  $message = str_replace("%link%", $prepend, $message);

  if(!mail($email, "Activate", $message, implode("\r\n", $headers)))
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
