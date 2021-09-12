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

  if(!isset($post['folder_id'])) throw new Exception("folder_id field is not set", E_FIELD_NOT_SET);
  $folder_id = trim($post['folder_id']);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `enabled`, `privileges`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('SELECT `account_id` FROM `folders` WHERE `folder_id` = ?', array("i"), array($folder_id));
  $folder_account_id = MySQL::stmt_result();
  $folder_account_id = $folder_account_id->fetch_assoc()['account_id'];
  if(is_null($folder_account_id)) throw new Exception("Folder doesn't exist", E_DOESNT_EXIST);

  MySQL::query('SELECT 0 FROM `files` WHERE `folder_id` = ? LIMIT 1', array("i"), array($folder_id));
  $files = MySQL::stmt_result();
  $files = $files->fetch_assoc();
  if(!is_null($files)) throw new Exception("Folder is not empty", E_EMPTY);

  MySQL::query('SELECT 0 FROM `folders` WHERE `parent_id` = ? LIMIT 1', array("i"), array($folder_id));
  $parent_id = MySQL::stmt_result();
  $parent_id = $parent_id->fetch_assoc();
  if(!is_null($parent_id)) throw new Exception("Folder is not empty", E_EMPTY);

  if($validation['account_id'] !== $folder_account_id)
  {
    if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  }

  MySQL::query('DELETE FROM `folders` WHERE `folder_id` = ?', array("i"), array($folder_id));
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
