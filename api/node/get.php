<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/node/get.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  MySQL::connect();
  MySQL::start_transaction();
  MySQL::query('SELECT
                `fqdn`,
                `registration_mode`,
                `sessions_timeout`,
                `activation_invitation_tokens_timeout`,
                `password_email_tokens_timeout`,
                `account_expire_delete_timeout`,
                `max_space`,
                `max_attics_count`,
                `max_folders_count`,
                `max_files_count`,
                `max_depth`
                FROM `node`');
  $result = MySQL::stmt_result();
  $result = $result->fetch_assoc();
  MySQL::stmt_close();
  MySQL::commit();

  $data = new stdClass();
  $data->fqdn = $result['fqdn'];
  $data->registration_mode = $result['registration_mode'];
  $data->session_timeout = $result['sessions_timeout'];
  $data->activation_invitation_tokens_timeout = $result['activation_invitation_tokens_timeout'];
  $data->password_email_tokens_timeout = $result['password_email_tokens_timeout'];
  $data->account_expire_delete_timeout = $result['account_expire_delete_timeout'];
  $data->max_space = $result['max_space'];
  $data->max_attics_count = $result['max_attics_count'];
  $data->max_folders_count = $result['max_folders_count'];
  $data->max_files_count = $result['max_files_count'];
  $data->max_depth = $result['max_depth'];

  send_response(SUCCESS, "", $data);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}
?>
