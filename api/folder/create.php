<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/folder/create.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['attic_id'])) throw new Exception("attic_id field is not set", E_FIELD_NOT_SET);
  $attic_id = trim($post['attic_id']);
  if(!valid_int($attic_id)) throw new Exception("attic_id field is not valid", E_FIELD_INVALID);

  $parent_id = 0;
  if(!isset($post['parent_id'])) $parent_id = null;
  if(isset($post['parent_id']))
  {
    $parent_id = trim($post['parent_id']);
    if(!valid_int($parent_id)) throw new Exception("parent_id field is not valid", E_FIELD_INVALID);
  }

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 255)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  //Add foreign folder
  if(isset($post['account_id']))
  {
    $account_id = trim($post['account_id']);

    create_foreign($session_id, $account_id, $attic_id, $parent_id, $name);
  }

  create($session_id, $attic_id, $parent_id, $name);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function create($session_id, $attic_id, $parent_id, $name)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `max_folders_count`, `current_folders_count`, `enabled`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  if($validation['current_folders_count'] >= $validation['max_folders_count']) throw new Exception("Max folders count reached", E_MAX_COUNT);

  MySQL::query('SELECT verify_folder_create(?, ?, ?) AS `code`', array("i", "i", "i"), array($validation['account_id'], $attic_id, $parent_id));
  $check = MySQL::stmt_result();
  $check = $check->fetch_assoc()['code'];

  switch ($check)
  {
    case 3:
      throw new Exception("Attic doesn't exist", E_DOESNT_EXIST);
      break;
    case 5:
      throw new Exception("Parent folder doesn't exist", E_DOESNT_EXIST);
      break;
    case 6:
      throw new Exception("Max folder depth reached", E_MAX_COUNT);
      break;
  }

  MySQL::query('INSERT INTO `folders`(`attic_id`, `account_id`, `parent_id`, `name`)
                VALUES (?, ?, ?, ?)', array("i", "i", "i", "s"), array($attic_id, $validation['account_id'], $parent_id, $name));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}

function create_foreign($session_id, $account_id, $attic_id, $parent_id, $name)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `enabled`, `max_folders_count`, `current_folders_count`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if($validation['privileges'] !== 0) throw new Exception("Account is not a admin", E_UNAUTHORIZED);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('SELECT `privileges` FROM `accounts` WHERE `account_id` = ?', array("i"), array($account_id));
  $foreign_privileges = MySQL::stmt_result();
  $foreign_privileges = $foreign_privileges->fetch_assoc()['privileges'];
  if($foreign_privileges < $validation['privileges']) throw new Exception("Foreign account has higher privileges", E_UNAUTHORIZED);

  if(is_null($foreign_privileges)) throw new Exception("Foreign account doesn't exist", E_DOESNT_EXIST);

  MySQL::query('SELECT verify_folder_create(?, ?, ?) AS `code`', array("i", "i", "i"), array($account_id, $attic_id, $parent_id));
  $check = MySQL::stmt_result();
  $check = $check->fetch_assoc()['code'];
  switch ($check)
  {
    case 3:
      throw new Exception("Attic doesn't exist", E_DOESNT_EXIST);
      break;
    case 4:
      throw new Exception("Attic doesn't belong to given account", E_DOESNT_EXIST);
      break;
    case 5:
      throw new Exception("Parent folder doesn't exist", E_DOESNT_EXIST);
      break;
    case 6:
      throw new Exception("Max folder depth reached", E_MAX_COUNT);
      break;
  }

  if($validation['current_folders_count'] >= $validation['max_folders_count']) throw new Exception("Max folders count reached", E_MAX_COUNT);

  MySQL::query('INSERT INTO `folders`(`attic_id`, `account_id`, `parent_id`, `name`)
                VALUES (?, ?, ?, ?)', array("i", "i", "i", "s"), array($attic_id, $account_id, $parent_id, $name));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
