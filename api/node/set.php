<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/node/set.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
  if(!isset($post['fqdn'])) throw new Exception("fqdn field is not set", E_FIELD_NOT_SET);
  if(!isset($post['registration_mode'])) throw new Exception("registration_mode field is not set", E_FIELD_NOT_SET);
  if(!isset($post['sessions_timeout'])) throw new Exception("sessions_timeout field is not set", E_FIELD_NOT_SET);
  if(!isset($post['activation_invitation_tokens_timeout'])) throw new Exception("activation_invitation_tokens_timeout field is not set", E_FIELD_NOT_SET);
  if(!isset($post['password_email_tokens_timeout'])) throw new Exception("password_email_tokens_timeout field is not set", E_FIELD_NOT_SET);
  if(!isset($post['account_expire_delete_timeout'])) throw new Exception("account_expire_delete_timeout field is not set", E_FIELD_NOT_SET);
  if(!isset($post['max_space'])) throw new Exception("max_space field is not set", E_FIELD_NOT_SET);
  if(!isset($post['max_attics_count'])) throw new Exception("max_attics_count field is not set", E_FIELD_NOT_SET);
  if(!isset($post['max_folders_count'])) throw new Exception("max_folders_count field is not set", E_FIELD_NOT_SET);
  if(!isset($post['max_files_count'])) throw new Exception("max_files_count field is not set", E_FIELD_NOT_SET);
  if(!isset($post['max_depth'])) throw new Exception("max_depth field is not set", E_FIELD_NOT_SET);

  $session_id = trim($post['session_id']);

  MySQL::connect();
  MySQL::start_transaction();
  MySQL::query('SELECT `enabled`, `activated`, `privileges` FROM `accounts` WHERE `account_id` = (SELECT `account_id` FROM `sessions` WHERE `session_id` = token_to_bin(?))', array("s"), array($session_id));
  $validation = MySQL::stmt_result();
  $validation = $validation->fetch_assoc();

  if(!$validation) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
  if($validation['privileges'] !== 0) throw new Exception("Account is not an admin", E_UNAUTHORIZED);
  if(!$validation['activated']) throw new Exception("Account is not activated", E_ACTIVATED);
  if(!$validation['enabled']) throw new Exception("Account is disabled", E_DISABLED);

  $fqdn = trim($post['fqdn']);
  if(!valid_str($post['fqdn'], 1, 255)) throw new Exception("fqdn field is not valid", E_FIELD_INVALID);

  $registration_mode = trim($post['registration_mode']);
  if(!valid_int($registration_mode, array(0, 2))) throw new Exception("registration_mode field is not valid", E_FIELD_INVALID);

  $sessions_timeout = trim($post['sessions_timeout']);
  if(!valid_int($sessions_timeout) || $sessions_timeout <= 0) throw new Exception("sessions_timeout field is not valid", E_FIELD_INVALID);

  $activation_invitation_tokens_timeout = trim($post['activation_invitation_tokens_timeout']);
  if(!valid_int($activation_invitation_tokens_timeout) || $activation_invitation_tokens_timeout <= 0) throw new Exception("activation_invitation_tokens_timeout field is not valid", E_FIELD_INVALID);

  $password_email_tokens_timeout = trim($post['password_email_tokens_timeout']);
  if(!valid_int($password_email_tokens_timeout) || $password_email_tokens_timeout <= 0) throw new Exception("password_email_tokens_timeout field is not valid", E_FIELD_INVALID);

  $account_expire_delete_timeout = trim($post['account_expire_delete_timeout']);
  if(!valid_int($account_expire_delete_timeout) || $account_expire_delete_timeout <= 0) throw new Exception("account_expire_delete_timeout field is not valid", E_FIELD_INVALID);

  $max_space = trim($post['max_space']);
  if(!valid_int($max_space) || $max_space <= 0) throw new Exception("max_space field is not valid", E_FIELD_INVALID);

  $max_attics_count = trim($post['max_attics_count']);
  if(!valid_int($max_attics_count) || $max_attics_count < 0) throw new Exception("max_attics_count field is not valid", E_FIELD_INVALID);

  $max_folders_count = trim($post['max_folders_count']);
  if(!valid_int($max_folders_count) || $max_folders_count < 0) throw new Exception("max_folders_count field is not valid", E_FIELD_INVALID);

  $max_files_count = trim($post['max_files_count']);
  if(!valid_int($max_files_count) || $max_files_count < 0) throw new Exception("max_files_count field is not valid", E_FIELD_INVALID);

  $max_depth = trim($post['max_depth']);
  if(!valid_int($max_depth) || $max_depth < 0) throw new Exception("max_depth field is not valid", E_FIELD_INVALID);

  MySQL::query('UPDATE `node` SET
                `fqdn` = ?,
                `registration_mode` = ?,
                `sessions_timeout` = ?,
                `activation_invitation_tokens_timeout` = ?,
                `password_email_tokens_timeout` = ?,
                `account_expire_delete_timeout` = ?,
                `max_space` = ?,
                `max_attics_count` = ?,
                `max_folders_count` = ?,
                `max_files_count` = ?,
                `max_depth` = ?',
                array("s", "i", "i", "i", "i", "i", "i", "i", "i", "i", "i"),
                array($fqdn, $registration_mode, $sessions_timeout, $activation_invitation_tokens_timeout, $password_email_tokens_timeout, $account_expire_delete_timeout, $max_space, $max_attics_count, $max_folders_count, $max_files_count, $max_depth));
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
