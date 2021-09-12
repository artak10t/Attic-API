<?php
  $root = $_SERVER['DOCUMENT_ROOT'];
  include_once("$root/v2/utils/mysql.php");
  include_once("$root/v2/utils/http.php");
  include_once("$root/v2/utils/errors.php");

  function get_attic_auth_info($sid)
  {
    try
    {
      MySQL::connect();
      MySQL::start_transaction();
      MySQL::query('CALL `get_attic_auth_info`(?)', array('s'), array($sid));

      $result = MySQL::stmt_result();
      $result = $result->fetch_assoc();

      MySQL::stmt_close();
      MySQL::commit();

      if($result['code'] !== 0) throw new Exception("Not logged in", E_NOT_LOGGED_IN);

      $status = new stdClass();
      $status->privileges = $result['privileges'];
      $status->enabled = $result['enabled'];
      $status->activated = $result['activated'];

      return $status;
    }
    catch(Exception $e){ send_response($e->getCode(), $e->getMessage()); }
  }
?>
