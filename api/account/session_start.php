<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/session_start.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['email'])) throw new Exception("email field is not set", E_FIELD_NOT_SET);
  if(!isset($post['password'])) throw new Exception("password field is not set", E_FIELD_NOT_SET);

  $email = trim($post['email']);
  if(!valid_email($email)) throw new Exception("email field is not valid", E_FIELD_INVALID);

  $password = trim($post['password']);
  if(!valid_password($password, 6, 32)) throw new Exception("password field is not valid", E_FIELD_INVALID);

  MySQL::connect();
  MySQL::start_transaction();
  MySQL::query('SELECT `account_id` FROM `accounts` WHERE `email` = ? AND `password_bin` = encrypt_string(?)', array("s", "s"), array($email, $password));
  $account_id = MySQL::stmt_result();
  $account_id = $account_id->fetch_assoc()['account_id'];

  if(!$account_id) throw new Exception("Invalid credentials", E_INVALID_CREDENTIALS);

  MySQL::query('SELECT create_token() AS `session_id`');
  $session_id = MySQL::stmt_result();
  $session_id = $session_id->fetch_assoc()['session_id'];

  MySQL::query('INSERT INTO `sessions` (`session_id`, `account_id`) VALUES (token_to_bin(?), ?)', array("s", "i"), array($session_id, $account_id));
  MySQL::query('UPDATE `accounts` SET `last_login` = UTC_TIMESTAMP() WHERE `account_id` = ?', array("s"), array($account_id));

  $data = new stdClass();
  $data->session_id = $session_id;
  MySQL::stmt_close();
  MySQL::commit();

  send_response(SUCCESS, "", $data);
}
catch(Exception $e)
{
  MySQL::stmt_close();
  MySQL::rollback();
  send_response($e->getCode(), $e->getMessage());
}
?>
