<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/attic/create.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 64)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  //Add foreign attic
  if(isset($post['account_id']))
  {
    $account_id = trim($post['account_id']);

    if(!isset($post['enabled'])) throw new Exception("enabled field is not set", E_FIELD_NOT_SET);
    $enabled = trim($post['enabled']);
    if(!valid_int($enabled, array(0, 1))) throw new Exception("enabled field is not valid", E_FIELD_INVALID);

    create_foreign($session_id, $account_id, $name, $enabled);
  }

  create($session_id, $name);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function create($session_id, $name)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id`, `max_attics_count`, `current_attics_count`, `enabled`, `activated` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  if($validation['current_attics_count'] >= $validation['max_attics_count']) throw new Exception("Max attics count reached", E_MAX_COUNT);

  MySQL::query('INSERT INTO `attics`(`account_id`, `name`, `enabled`, `access`)
                VALUES (?, ?, 1, 0)', array("i", "s"), array($validation['account_id'], $name));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}

function create_foreign($session_id, $account_id, $name, $enabled)
{
  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `enabled`, `max_attics_count`, `current_attics_count`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
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

  if($validation['current_attics_count'] >= $validation['max_attics_count']) throw new Exception("Max attics count reached", E_MAX_COUNT);

  MySQL::query('INSERT INTO `attics`(`account_id`, `name`, `enabled`, `access`)
                VALUES (?, ?, ?, 0)', array("s", "s", "i"), array($account_id, $name, $enabled));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
