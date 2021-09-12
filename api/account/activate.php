<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/utils/mysql.php");
include_once("$root/utils/http.php");
include_once("$root/utils/errors.php");
include_once("$root/utils/validations.php");

try
{
  if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['help'])) header('Location: '."/help/account/activate.html");

  if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

  $post = decode_post();

  if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);

  $token = trim($post['token']);

  MySQL::connect();
  MySQL::start_transaction();

  MySQL::query('SELECT `account_id` FROM `tokens` WHERE `token_bin` = token_to_bin(?) AND `token_type` = 0', array("s"), array($token));
  $account_id = MySQL::stmt_result();
  $account_id = $account_id->fetch_assoc();
  if(!$account_id) throw new Exception("Invalid token", E_INVALID_TOKEN);

  MySQL::query('UPDATE `accounts` SET `activated` = 1 WHERE `account_id` = ?', array("i"), array($account_id));
  MySQL::query('DELETE FROM `tokens` WHERE `token_bin` = token_to_bin(?)', array("s"), array($token));

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
