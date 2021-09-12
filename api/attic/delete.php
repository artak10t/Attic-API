<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/attic/delete.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['attic_id'])) throw new Exception("attic_id field is not set", E_FIELD_NOT_SET);
  $attic_id = trim($post['attic_id']);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `enabled`, `privileges`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('SELECT `account_id` FROM `attics` WHERE `attic_id` = ?', array("i"), array($attic_id));
  $attic_account_id = MySQL::stmt_result();
  $attic_account_id = $attic_account_id->fetch_assoc()['account_id'];

  MySQL::query('SELECT 0 FROM `files` WHERE `attic_id` = ? LIMIT 1', array("i"), array($attic_id));
  $files = MySQL::stmt_result();
  $files = $files->fetch_assoc();

  if(!is_null($files)) throw new Exception("Attic is not empty", E_CANT_DELETE);
  if(is_null($attic_account_id)) throw new Exception("Attic doesn't exist", E_DOESNT_EXIST);

  //Check if foreign attic
  if($validation['account_id'] !== $attic_account_id)
  {
    if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  }

  MySQL::query('DELETE FROM `attics` WHERE `attic_id` = ?', array("i"), array($attic_id));
  MySQL::stmt_close();
  MySQL::commit();

  send_response(SUCCESS, "");
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}
?>
