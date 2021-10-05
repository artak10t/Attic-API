<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/errors.php");

function send_response($errno, $error, $data = null)
{
  if(is_null($data))
    $data = array();

  $result = array
  (
    "code" => $errno,
    "msg" => $error,
    "data" => $data
  );
  $result = json_encode($result, JSON_UNESCAPED_UNICODE);
  if ($result === false)
    $result ='{"code":'.E_JSON_ENCODE.',"msg":"Invalid json encoding","data":{}}';
  echo $result;
}

function decode_post()
{
  $post = file_get_contents("php://input");
  $result = json_decode($post, true);
  if (is_null($result))
    throw new Exception("Invalid json decoding", E_JSON_DECODE);
  return $result;
}
