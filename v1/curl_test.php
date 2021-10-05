<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/curl.php");
include_once("$root/v1/utils/http.php");

$content = array(
  "session_id" => "58AA4F55381F68D62F8F304D5D0FE071193D88B51F3A9F1B6135A095AF651C3E",
  "email" => "admin@attic.com",
  "password" => "Admin0",
  "share_id" => 56
);

try{
  $curl = new CURL();
  $curl->send("https://attic.com/v1/api/share/refresh.php", json_encode($content, JSON_UNESCAPED_UNICODE));
  $response = json_decode($curl->get_response_body(), true);

  echo "<pre>";
  echo json_encode($response, JSON_PRETTY_PRINT);
  echo "</pre>";

}catch(Exception $e){
  send_response($e->getCode(), $e->getMessage());
}

?>
