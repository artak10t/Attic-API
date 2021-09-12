<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/delete.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

  $session_id = trim($post['session_id']);

  MySQL::connect();
  MySQL::start_transaction();

  if(isset($post['account_id']))
  {
    $account_id = trim($post['account_id']);

    delete_foreign($session_id, $account_id);
  }

  delete($session_id);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function delete($session_id)
{
  MySQL::query('SELECT `account_id`, `enabled` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(is_null($validation)) throw new Exception("Invalid session", E_NOT_LOGGED_IN);

  MySQL::query('UPDATE `accounts` SET `delete_on` = DATE_ADD(UTC_TIMESTAMP(), INTERVAL (SELECT `account_expire_delete_timeout` FROM `node`) MINUTE)
                WHERE `account_id` = ?', array("i"), array($validation['account_id']));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}

function delete_foreign($session_id, $account_id)
{
  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(is_null($validation)) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_NOT_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('DELETE FROM `accounts` WHERE `account_id` = ?', array("i"), array($account_id));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
