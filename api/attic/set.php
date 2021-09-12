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

  if(!isset($post['attic_id'])) throw new Exception("attic_id field is not set", E_FIELD_NOT_SET);
  $attic_id = trim($post['attic_id']);

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 64)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  if(!isset($post['access'])) throw new Exception("access field is not set", E_FIELD_NOT_SET);
  $access = trim($post['access']);
  if(!valid_int($access, array(0, 2))) throw new Exception("access field is not valid", E_FIELD_INVALID);

  if(!isset($post['description'])) throw new Exception("description field is not set", E_FIELD_NOT_SET);
  $description = trim($post['description']);
  if($description != "" && !valid_str($post['description'], 1, 500)) throw new Exception("description field is not valid", E_FIELD_INVALID);

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

  if(is_null($attic_account_id)) throw new Exception("Attic doesn't exist", E_DOESNT_EXIST);

  //Set foreign attic
  if($validation['account_id'] !== $attic_account_id)
  {
    if(!isset($post['enabled'])) throw new Exception("enabled field is not set", E_FIELD_NOT_SET);
    $enabled = trim($post['enabled']);
    if(!valid_int($enabled, array(0, 1))) throw new Exception("enabled field is not valid", E_FIELD_INVALID);

    set_foreign($validation, $attic_id, $name, $access, $description, $enabled);
  }

  MySQL::query('UPDATE `attics` SET
                `name` = ?,
                `access` = ?,
                `description` = ?
                WHERE `attic_id` = ?',
                array("s", "i", "s", "i"), array($name, $access, $description, $attic_id));

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

function set_foreign($validation, $attic_id, $name, $access, $description, $enabled)
{
  if($validation['privileges'] > 1) throw new Exception("Account is not a moderator/admin", E_UNAUTHORIZED);

  MySQL::query('SELECT `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `attics` WHERE `attic_id` = ?)', array("i"), array($attic_id));
  $foreign_privileges = MySQL::stmt_result();
  $foreign_privileges = $foreign_privileges->fetch_assoc()['privileges'];
  if($foreign_privileges < $validation['privileges']) throw new Exception("Foreign account has higher privileges", E_UNAUTHORIZED);

  MySQL::query('UPDATE `attics` SET
                `name` = ?,
                `enabled` = ?,
                `access` = ?,
                `description` = ?
                WHERE `attic_id` = ?',
                array("s", "i", "i", "s", "i"), array($name, $enabled, $access, $description, $attic_id));

  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
