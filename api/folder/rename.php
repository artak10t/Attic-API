<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/folder/rename.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['folder_id'])) throw new Exception("folder_id field is not set", E_FIELD_NOT_SET);
  $folder_id = trim($post['folder_id']);

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 255)) throw new Exception("name field is not valid", E_FIELD_INVALID);

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

  if($validation['account_id'] !== $folder_account_id)
  {
    if($validation['privileges'] > 1) throw new Exception("Account is not an moderator/admin", E_UNAUTHORIZED);
  }

  MySQL::query('UPDATE `folders` SET `name` = ? WHERE `folder_id` = ?', array("s", "i"), array($name, $folder_id));
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
