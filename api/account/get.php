<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/get.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  $session_id = trim($post['session_id']);

  MySQL::connect();
  MySQL::start_transaction();
  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);

  if(isset($post['account_id']))
  {
    if($validation['privileges'] > 1) throw new Exception("Account is not a moderator/admin", E_UNAUTHORIZED);
    if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
    if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

    $account_id = trim($post['account_id']);
    get_foreign($account_id);
  }

  get($session_id);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}

function get($session_id)
{
  MySQL::query('SELECT
                `name`,
                `email`,
                `enabled`,
                `activated`,
                `access`,
                `privileges`,
                `max_space`,
                `current_space`,
                `max_attics_count`,
                `current_attics_count`,
                `max_folders_count`,
                `current_folders_count`,
                `max_files_count`,
                `current_files_count`,
                `created`,
                `delete_on`,
                `contact_email`,
                `contact_phone`,
                `description`
                FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $result = MySQL::stmt_result();
  $result = $result->fetch_assoc();
  MySQL::stmt_close();
  MySQL::commit();

  $data = new stdClass();
  $data->name = $result['name'];
  $data->email = $result['email'];
  $data->enabled = $result['enabled'];
  $data->activated = $result['activated'];
  $data->access = $result['access'];
  $data->privileges = $result['privileges'];
  $data->max_space = $result['max_space'];
  $data->current_space = $result['current_space'];
  $data->max_attics_count = $result['max_attics_count'];
  $data->current_attics_count = $result['current_attics_count'];
  $data->max_folders_count = $result['max_folders_count'];
  $data->current_folders_count = $result['current_folders_count'];
  $data->max_files_count = $result['max_files_count'];
  $data->current_files_count = $result['current_files_count'];
  $data->created = $result['created'];
  $data->delete_on = $result['delete_on'];
  $data->contact_email = $result['contact_email'];
  $data->contact_phone = $result['contact_phone'];
  $data->description = $result['description'];

  send_response(SUCCESS, "", $data);
}

function get_foreign($account_id)
{
  MySQL::query('SELECT
                `name`,
                `email`,
                `enabled`,
                `activated`,
                `access`,
                `privileges`,
                `max_space`,
                `current_space`,
                `max_attics_count`,
                `current_attics_count`,
                `max_folders_count`,
                `current_folders_count`,
                `max_files_count`,
                `current_files_count`,
                `created`,
                `delete_on`,
                `contact_email`,
                `contact_phone`,
                `description`
                FROM `accounts` WHERE `account_id` = ?', array("i"), array($account_id));
  $result = MySQL::stmt_result();
  $result = $result->fetch_assoc();
  MySQL::stmt_close();
  MySQL::commit();

  $data = new stdClass();
  $data->name = $result['name'];
  $data->email = $result['email'];
  $data->enabled = $result['enabled'];
  $data->activated = $result['activated'];
  $data->access = $result['access'];
  $data->privileges = $result['privileges'];
  $data->max_space = $result['max_space'];
  $data->current_space = $result['current_space'];
  $data->max_attics_count = $result['max_attics_count'];
  $data->current_attics_count = $result['current_attics_count'];
  $data->max_folders_count = $result['max_folders_count'];
  $data->current_folders_count = $result['current_folders_count'];
  $data->max_files_count = $result['max_files_count'];
  $data->current_files_count = $result['current_files_count'];
  $data->created = $result['created'];
  $data->delete_on = $result['delete_on'];
  $data->contact_email = $result['contact_email'];
  $data->contact_phone = $result['contact_phone'];
  $data->description = $result['description'];

  send_response(SUCCESS, "", $data);
}
?>
