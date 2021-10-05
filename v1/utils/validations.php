<?php
  //Used to validate inputs
  function valid_password($str, $min_length, $max_length){
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length
      && strpos($str, " ") === false && preg_match("/[0-9]/", $str) === 1 && strtolower($str) !== $str)
      return true;
    return false;
  }

  function valid_alphanum_str($str, $min_length, $max_length, $special_chars = ""){
    // allow [A-Z], [a-z], [0-9] and spectal chars
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length){
      $special_chars = preg_quote($special_chars, '/');
      if (preg_match("/^[a-zA-Z0-9".$special_chars."]*$/", $str) === 1)
        return true;
    }
    return false;
  }

  function valid_restricted_str($str, $min_length, $max_length, $restricted_chars = ""){
    // allow everything except restricted chars
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length){
      $restricted_chars = preg_quote($restricted_chars, '/');
      if (preg_match("/^[^".$restricted_chars."]*$/", $str) === 1)
        return true;
    }
    return false;
  }

  function valid_email($email){
    if(filter_var($email, FILTER_VALIDATE_EMAIL))
      return true;
    return false;
  }

  function valid_int($val, $min = null, $max = null){
    if (is_null($min)) $min = PHP_INT_MIN;
    if (is_null($max)) $max = PHP_INT_MAX;
    $filtered = filter_var($val, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min , "max_range"=> $max]]);
    if($filtered || $filtered === 0)
      return true;
    return false;
  }

  function valid_str($str, $min_length, $max_length){
    if (is_string($str) && mb_strlen($str) >= $min_length && mb_strlen($str) <= $max_length)
      return true;
    return false;
  }
?>
