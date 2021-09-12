<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/set.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  MySQL::connect();
  MySQL::start_transaction();
  //Check if user logged in
  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);

  //Validate fields
  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  if(!isset($post['access'])) throw new Exception("access field is not set", E_FIELD_NOT_SET);
  if(!isset($post['contact_email'])) throw new Exception("contact_email field is not set", E_FIELD_NOT_SET);
  if(!isset($post['contact_phone'])) throw new Exception("contact_phone field is not set", E_FIELD_NOT_SET);
  if(!isset($post['description'])) throw new Exception("description field is not set", E_FIELD_NOT_SET);

  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 64)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  $access = trim($post['access']);
  if(!valid_int($access, array(0, 2))) throw new Exception("access field is not valid", E_FIELD_INVALID);

  $contact_email = trim($post['contact_email']);
  if($contact_email != "" && !valid_email($post['contact_email'])) throw new Exception("contact_email field is not valid", E_FIELD_INVALID);

  $contact_phone = trim($post['contact_phone']);
  if($contact_phone != "" && !valid_str($post['contact_phone'], 1, 32)) throw new Exception("contact_phone field is not valid", E_FIELD_INVALID);

  $description = trim($post['description']);
  if($description != "" && !valid_str($post['description'], 1, 500)) throw new Exception("description field is not valid", E_FIELD_INVALID);

  //Check if trying to set foreign account
  if(isset($post['account_id']))
  {
    if($validation['privileges'] > 1) throw new Exception("Account is not a moderator/admin", E_UNAUTHORIZED);
    if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
    if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

    //Validate fields
    if(!isset($post['enabled'])) throw new Exception("enabled field is not set", E_FIELD_NOT_SET);
    $enabled = trim($post['enabled']);
    if(!valid_int($enabled, array(0, 1))) throw new Exception("enabled field is not valid", E_FIELD_INVALID);

    if(!isset($post['activated'])) throw new Exception("activated field is not set", E_FIELD_NOT_SET);
    $activated = trim($post['activated']);
    if(!valid_int($activated, array(0, 1))) throw new Exception("activated field is not valid", E_FIELD_INVALID);

    if(!isset($post['max_space'])) throw new Exception("max_space field is not set", E_FIELD_NOT_SET);
    $max_space = trim($post['max_space']);
    if(!valid_int($max_space) || $max_space <= 0) throw new Exception("max_space field is not valid", E_FIELD_INVALID);

    if(!isset($post['max_attics_count'])) throw new Exception("max_attics_count field is not set", E_FIELD_NOT_SET);
    $max_attics_count = trim($post['max_attics_count']);
    if(!valid_int($max_attics_count) || $max_attics_count < 0) throw new Exception("max_attics_count field is not valid", E_FIELD_INVALID);

    if(!isset($post['max_folders_count'])) throw new Exception("max_folders_count field is not set", E_FIELD_NOT_SET);
    $max_folders_count = trim($post['max_folders_count']);
    if(!valid_int($max_folders_count) || $max_folders_count < 0) throw new Exception("max_folders_count field is not valid", E_FIELD_INVALID);

    if(!isset($post['max_files_count'])) throw new Exception("max_files_count field is not set", E_FIELD_NOT_SET);
    $max_files_count = trim($post['max_files_count']);
    if(!valid_int($max_files_count) || $max_files_count < 0) throw new Exception("max_files_count field is not valid", E_FIELD_INVALID);

    $account_id = trim($post['account_id']);
    set_foreign($account_id, $validation, $name, $access, $contact_email, $contact_phone, $description,
                $enabled, $activated, $max_space, $max_attics_count, $max_folders_count, $max_files_count);
  }

  set($session_id, $name, $access, $contact_email, $contact_phone, $description);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function set($session_id, $name, $access, $contact_email, $contact_phone, $description)
{
  MySQL::query('UPDATE `accounts` SET
                `name` = ?,
                `access` = ?,
                `contact_email` = ?,
                `contact_phone` = ?,
                `description` = ?
                WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))',
                array("s", "i", "s", "s", "s", "s"), array($name, $access, $contact_email, $contact_phone, $description, $session_id));
  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}

function set_foreign($account_id, $validation, $name, $access, $contact_email, $contact_phone, $description,
                     $enabled, $activated, $max_space, $max_attics_count, $max_folders_count, $max_files_count)
{
  //Foreign account validation
  MySQL::query('SELECT `privileges` FROM `accounts` WHERE `account_id` = ?', array("i"), array($account_id));
  $foreign_privileges = MySQL::stmt_result();
  $foreign_privileges = $foreign_privileges->fetch_assoc()['privileges'];
  if($foreign_privileges < $validation['privileges']) throw new Exception("Foreign account has higher privileges", E_UNAUTHORIZED);

  if(is_null($foreign_privileges)) throw new Exception("Foreign account doesn't exist", E_DOESNT_EXIST);

  MySQL::query('UPDATE `accounts` SET
                `name` = ?,
                `enabled` = ?,
                `activated` = ?,
                `access` = ?,
                `max_space` = ?,
                `max_attics_count` = ?,
                `max_folders_count` = ?,
                `max_files_count` = ?,
                `contact_email` = ?,
                `contact_phone` = ?,
                `description` = ?
                WHERE `account_id` = ?',
                array("s", "i", "i", "i", "i", "i", "i", "i", "s", "s", "s", "i"),
                array($name, $enabled, $activated, $access, $max_space, $max_attics_count, $max_folders_count, $max_files_count, $contact_email, $contact_phone, $description, $account_id));
  MySQL::stmt_close();
  MySQL::commit();
  send_response(SUCCESS, "");
}
?>
