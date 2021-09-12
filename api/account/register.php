<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/register.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['name'])) throw new Exception("name field is not set", E_FIELD_NOT_SET);
  if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
  if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);

  $name = trim($post['name']);
  if(!valid_alphanum_str($name, 1, 64)) throw new Exception("name field is not valid", E_FIELD_INVALID);

  $email = trim($post['email']);
  if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);

  $password = trim($post['password']);
  if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);

  MySQL::connect();
  MySQL::start_transaction();
  MySQL::query('SELECT `registration_mode` FROM `node`');
  $registration_mode = MySQL::stmt_result();
  $registration_mode = $registration_mode->fetch_assoc()['registration_mode'];

  if(isset($post['session_id']))
  {
    $session_id = trim($post['session_id']);

    if(!isset($post['enabled'])) throw new Exception("enabled field is not set", E_FIELD_NOT_SET);
    $enabled = trim($post['enabled']);
    if(!valid_int($enabled, array(0, 1))) throw new Exception("enabled field is not valid", E_FIELD_INVALID);

    if(!isset($post['privileges'])) throw new Exception("privileges field is not set", E_FIELD_NOT_SET);
    $privileges = trim($post['privileges']);
    if(!valid_int($privileges, array(0, 2))) throw new Exception("privileges field is not valid", E_FIELD_INVALID);

    register_foreign($name, $email, $password, $session_id, $enabled, $privileges);
  }

  switch ($registration_mode) {
    case 0:
      throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
      break;
    case 1:
      if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);
      $token = trim($post['token']);

      MySQL::query('SELECT 0 FROM `tokens` WHERE `token_type` = 1 AND `token_bin` = token_to_bin(?)', array("s"), array($token));
      $token_exists = MySQL::stmt_result();
      $token_exists = $token_exists->fetch_assoc();
      if(!$token_exists) throw new Exception("Invalid token", E_INVALID_TOKEN);

      register($name, $email, $password);
      break;
    case 2:
      register($name, $email, $password);
      break;
  }
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function register($name, $email, $password)
{
  MySQL::query('INSERT INTO `accounts`(
      `name`,
      `email`,
      `password_bin`,
      `enabled`,
      `activated`,
      `access`,
      `privileges`,
      `max_space`,
      `max_attics_count`,
      `max_folders_count`,
      `max_files_count`
    ) VALUES (
      ?,
      ?,
      encrypt_string(?),
      1,
      0,
      0,
      2,
      (SELECT `max_space` FROM `node`),
      (SELECT `max_attics_count` FROM `node`),
      (SELECT `max_folders_count` FROM `node`),
      (SELECT `max_files_count` FROM `node`)
    )', array("s", "s", "s"), array($name, $email, $password));
    MySQL::stmt_close();
    MySQL::commit();

    send_response(SUCCESS, "");
}

function register_foreign($name, $email, $password, $session_id, $enabled, $privileges)
{
  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?)) LOCK IN SHARE MODE', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  MySQL::query('INSERT INTO `accounts`(
      `name`,
      `email`,
      `password_bin`,
      `enabled`,
      `activated`,
      `access`,
      `privileges`,
      `max_space`,
      `max_attics_count`,
      `max_folders_count`,
      `max_files_count`
    ) VALUES (
      ?,
      ?,
      encrypt_string(?),
      ?,
      1,
      0,
      ?,
      (SELECT `max_space` FROM `node`),
      (SELECT `max_attics_count` FROM `node`),
      (SELECT `max_folders_count` FROM `node`),
      (SELECT `max_files_count` FROM `node`)
    )', array("s", "s", "s", "i", "i"), array($name, $email, $password, $enabled, $privileges));
    MySQL::stmt_close();
    MySQL::commit();

    send_response(SUCCESS, "");
}
?>
