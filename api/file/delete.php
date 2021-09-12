<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/folder/delete.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['file_id'])) throw new Exception("file_id field is not set", E_FIELD_NOT_SET);
  $file_id = trim($post['file_id']);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `enabled`, `privileges`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('SELECT `account_id` FROM `files` WHERE `files_id` = ?', array("i"), array($file_id));
  $file_account_id = MySQL::stmt_result();
  $file_account_id = $file_account_id->fetch_assoc()['account_id'];
  if(is_null($file_account_id)) throw new Exception("File doesn't exist", E_DOESNT_EXIST);

  if($validation['account_id'] !== $folder_account_id)
  {
    if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  }

  MySQL::query('DELETE FROM `files` WHERE `file_id` = ?', array("i"), array($file_id));
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
