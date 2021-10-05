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

    if (!ENABLE_LOCAL_SHARING) throw new Exception("Sharing disabled", E_LOCAL_SHARING_DISABLED);

    $post = decode_post();

    if(!isset($post['file_id'])) throw new Exception("file_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['rcpt'])) throw new Exception("rcpt field is not set", E_FIELD_NOT_SET);

    $file_id = trim($post['file_id']);
    if(!valid_str($file_id, 36, 36)) throw new Exception("file_id field is not valid", E_FIELD_INVALID);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);
    $rcpt = trim($post['rcpt']);
    if(!valid_email($rcpt)) throw new Exception("rcpt field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if session_id is valid, and account is enabled
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `disabled`, `reason` FROM `v_sessions` WHERE `session_id` = token_to_bin(?) LOCK IN SHARE MODE', array("s"), array($session_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $account_id = $arr["account_id"];
      $stmt->close();

      // check if file_id is valid, and owned by the same account
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `account_name` FROM `v_files` WHERE `file_id` = uuid_to_bin(?) LOCK IN SHARE MODE', array("s"), array($file_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("File doesn't exist", E_DOESNT_EXIST);
      if ($arr["account_id"] != $account_id) throw new Exception("File is owned by another account", E_UNAUTHORIZED);
      $sender = $arr["account_name"] .'@'.FQDN;
      $stmt->close();

      // get sharing id, hash and token
      $stmt = $mysql->query("CALL create_sharing(?, ?)", array("s", "s"), array($sender, $rcpt));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $arr = $result->fetch_assoc();
      $new_share = $arr["new_share"];
      $share_id = $arr["share_id"];
      $share_token = $arr["share_token"];
      $stmt->close();

      // if this is a new share, try to send sharing request to remote attic
      if ($new_share){
        $curl = new CURL();
        $request = array(
          "sender" => $sender,
          "rcpt" => $rcpt,
          "token" => $share_token
        );
        $request = json_encode($request, JSON_UNESCAPED_UNICODE);
        $curl->send("https://".substr(strrchr($rcpt, "@"), 1)."/v1/api/share/accept.php", $request);
        $response = json_decode($curl->get_response_body(), true);
        if (is_null($response))
          throw new Exception("Invalid json decoding: ".$curl->get_response_body(), E_JSON_DECODE);
        if ($response["code"] != 0) throw new Exception($response["msg"], $response["code"]);
      }
      // Insert sharing hash into local_shares_assocs
      $stmt = $mysql->query("INSERT IGNORE INTO `local_shares_assocs`(`file_id`, `share_id`) VALUES (uuid_to_bin(?), ?)", array("s", "i"), array($file_id, $share_id));
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
