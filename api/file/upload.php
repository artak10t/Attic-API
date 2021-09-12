<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/filde/upload.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['folder_id'])) throw new Exception("folder_id field is not set", E_FIELD_NOT_SET);
  $folder_id = trim($post['folder_id']);
  if(!valid_int($folder_id)) throw new Exception("folder_id field is not valid", E_FIELD_INVALID);

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 255)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `max_files_count`, `current_files_count`, `enabled`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  if($validation['current_files_count'] >= $validation['max_files_count']) throw new Exception("Max files count reached", E_MAX_COUNT);

  MySQL::query('SELECT verify_folder_create(?, ?, ?) AS `code`', array("i", "i", "i"), array($validation['account_id'], $attic_id, $folder_id));
  $check = MySQL::stmt_result();
  $check = $check->fetch_assoc()['code'];

  switch ($check)
  {
    case 3:
      throw new Exception("Attic doesn't exist", E_DOESNT_EXIST);
      break;
    case 5:
      throw new Exception("Folder doesn't exist", E_DOESNT_EXIST);
      break;
  }

  MySQL::query('SELECT `storage_path` FROM `node`');
  $path = MySQL::stmt_result();
  $path = $path->fetch_assoc()['storage_path'];

  $target_file = $path.$name;

  if(!isset($post['submit'])) throw new Exception("submit field is not set", E_FIELD_NOT_SET);
  $submit = trim($post['submit']);

  MySQL::query('INSERT INTO `files`(`attic_id`, `account_id`, `folder_id`, `name`, `enabled`, `access`, `comments_on`, `size`, `current_size`)
                VALUES (?, ?, ?, ?, 1, 0, 0, $_FILES["fileToUpload"]["size"], 0)', array("i", "i", "i", "s"), array($attic_id, $validation['account_id'], $folder_id, $name));

  move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}
?>
