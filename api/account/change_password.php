<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/change_password.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);

  $password = trim($post['password']);
  if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);

  if(isset($post['account_id']) || isset($post['session_id']))
  {
    if(!isset($post['account_id'])) throw new Exception("account_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

    $account_id = trim($post['account_id']);
    $session_id = trim($post['session_id']);
    change_password_foreign($session_id, $account_id, $password);
  }

  if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);
  $token = trim($post['token']);
  change_password($token, $password);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function change_password($token, $password)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id` FROM `tokens` WHERE `token_bin` = token_to_bin(?) AND `token_type` = 2', array("s"), array($token));
  $account_id = MySQL::stmt_result();
  $account_id = $account_id->fetch_assoc();
  if(is_null($account_id)) throw new Exception("Invalid token", E_INVALID_TOKEN);

  MySQL::query('UPDATE `accounts` SET `password_bin` = encrypt_string(?) WHERE `account_id` = ?', array("s", "i"), array($password, $account_id));
  MySQL::query('DELETE FROM `tokens` WHERE `token_bin` = token_to_bin(?)', array("s"), array($token));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}

function change_password_foreign($session_id, $account_id, $password)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(is_null($validation)) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if($validation['privileges'] > 1) throw new Exception("Account is not a moderator/admin", E_UNAUTHORIZED);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('SELECT `privileges` FROM `accounts` WHERE `account_id` = ?', array("i"), array($account_id));
  $foreign_privileges = MySQL::stmt_result();
  $foreign_privileges = $foreign_privileges->fetch_assoc()['privileges'];
  if($foreign_privileges < $validation['privileges']) throw new Exception("Foreign account has higher privileges", E_UNAUTHORIZED);

  if(is_null($foreign_privileges)) throw new Exception("Foreign account doesn't exist", E_DOESNT_EXIST);

  MySQL::query('UPDATE `accounts` SET `password_bin` = encrypt_string(?) WHERE `account_id` = ?', array("s", "i"), array($password, $account_id));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
