<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/errors.php");

  class CURL{
    private $response_headers;
    private $response_body;
    private $headers = array(
      "User-Agent: attic/1.0 (linux)",
      "Content-Type: application/json",
      "Pragma: no-cache",
      "Cache-Control: no-cache"
    );

    public function get_response_headers(){
      return $this->response_headers;
    }

    public function get_response_body(){
      return $this->response_body;
    }

    public function send($url, $content){
      $curl = curl_init();
      if (!curl_setopt($curl, CURLOPT_VERBOSE, true)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, REMOTE_ATTIC_API_TIMEOUT)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_TIMEOUT, REMOTE_ATTIC_API_TIMEOUT)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_RETURNTRANSFER, true)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_POST, true)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_HEADER, true)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_POSTFIELDS, $content)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_URL, $url)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      if (!curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers)) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      $response = curl_exec($curl);
      if ($response === false) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      if ($header_size === false) throw new Exception("CURL: ".curl_error($curl), CURL_ERROR);
      $this->response_headers = $this->explode_response_headers(substr($response, 0, $header_size));
      $this->response_body = substr($response, $header_size);
      $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if ($http_code <> 200) throw new Exception("CURL: ".$http_code, CURL_ERROR);
      curl_close($curl);
    }

    private function explode_response_headers($text){
      $result = array();
      $text = substr($text, 0, strpos($text, "\r\n\r\n"));
      foreach (explode("\r\n", $text) as $i => $line){
        if($i === 0){
          $result['http_code'] = $line;
        }else{
          list ($key, $value) = explode(': ', $line);
          $result[$key] = $value;
        }
      }
      return $result;
    }

  }
?>
