<?php
  //Used to validate inputs
  function valid_password($str, $min_length, $max_length){
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length
      && strpos($str, " ") === false && preg_match("/[0-9]/", $str) === 1 && strtolower($str) !== $str)
      return true;
    return false;
  }

  function valid_alphanum_str($str, $min_length, $max_length, $special_chars = ""){
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length){
      $special_chars = preg_quote($special_chars, '/');
      if (preg_match("/^[a-zA-Z0-9".$special_chars."]*$/", $str) === 1)
        return true;
    }
    return false;
  }

  function valid_email($email)
  {
    if(filter_var($email, FILTER_VALIDATE_EMAIL))
      return true;

    return false;
  }

  function valid_int($val, $range = null){
    if (is_null($range)){
      $filtered = filter_var($val, FILTER_VALIDATE_INT);
    } else
      $filtered = filter_var($val, FILTER_VALIDATE_INT, ["options" => ["min_range" => $range[0] , "max_range"=> $range[1]]]);
    if($filtered || $filtered === 0)
      return true;
    return false;
  }

  function valid_str($str, $min_length, $max_length){
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length)
      return true;
    return false;
  }

  function pr($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
  }

  function error($str){
    echo $str;
    exit(1);
  }
?>
