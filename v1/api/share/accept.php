<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");
include_once("$root/v1/utils/http.php");
include_once("$root/v1/utils/errors.php");
include_once("$root/v1/utils/consts.php");
include_once("$root/v1/utils/validations.php");
include_once("$root/v1/utils/curl.php");

$mysql = new MYSQL();
try{
  try{
    if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);

    if (!ACCEPT_REMOTE_SHARING) throw new Exception("Sharing not accepted", E_REMOTE_SHARING_DISABLED);

    $post = decode_post();

    if(!isset($post['sender'])) throw new Exception("sender field is not set", E_FIELD_NOT_SET);
    if(!isset($post['rcpt'])) throw new Exception("rcpt field is not set", E_FIELD_NOT_SET);
    if(!isset($post['token'])) throw new Exception("token field is not set", E_FIELD_NOT_SET);

    $sender = trim($post['sender']);
    if(!valid_email($sender)) throw new Exception("sender field is not valid", E_FIELD_INVALID);
    $rcpt = trim($post['rcpt']);
    if(!valid_email($rcpt)) throw new Exception("rcpt field is not valid", E_FIELD_INVALID);
    $token = trim($post['token']);
    if(!valid_str($token, 64, 64)) throw new Exception("token field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);
    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // Insert sharing token into remote_shares
      $stmt = $mysql->query("CALL `accept_sharing`(?, ?, token_to_bin(?))", array("s", "s", "s"), array($sender, $rcpt, $token));

      if ($mysql->deadlock()) continue;
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    send_response(SUCCESS, "");
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
