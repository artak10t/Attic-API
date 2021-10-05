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

    if(!isset($post['share_id'])) throw new Exception("share_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);

    $share_id = trim($post['share_id']);
    if(!valid_int($share_id, 1)) throw new Exception("share_id field is not valid", E_FIELD_INVALID);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);

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

      // check if share_id is valid, and owned by the same account
      $stmt = $mysql->query('SELECT bin_to_uuid(`account_id`) AS `account_id`, `account_name`, `rcpt` FROM `v_local_shares` WHERE `share_id` = ? LOCK IN SHARE MODE', array("i"), array($share_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Share doesn't exist", E_DOESNT_EXIST);
      if ($arr["account_id"] != $account_id) throw new Exception("Share is owned by another account", E_UNAUTHORIZED);
      $rcpt = $arr["rcpt"];
      $sender = $arr["account_name"] .'@'.FQDN;
      $stmt->close();


      // create new token
      $stmt = $mysql->query("SELECT create_token()");
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $arr = $result->fetch_array();
      $token = $arr[0];
      $stmt->close();

      // try to refrest sharing token in remote attic
      $curl = new CURL();
      $request = array(
        "sender" => $sender,
        "rcpt" => $rcpt,
        "token" => $token
      );
      $request = json_encode($request, JSON_UNESCAPED_UNICODE);
      $curl->send("https://".substr(strrchr($rcpt, "@"), 1)."/v1/api/share/accept.php", $request);
      $response = json_decode($curl->get_response_body(), true);
      if (is_null($response))
        throw new Exception("Invalid json decoding: ".$curl->get_response_body(), E_JSON_DECODE);
      if ($response["code"] != 0) throw new Exception($response["msg"], $response["code"]);

      // Update token in local_shares
      $stmt = $mysql->query("UPDATE `local_shares` SET `token` = token_to_bin(?) WHERE `share_id` = ?", array("s", "i"), array($token, $share_id));
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
