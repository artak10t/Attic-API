<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/validations.php");

define("FILTER_STR", 1);
define("FILTER_INT", 2);
define("FILTER_BOOL", 3);
define("FILTER_UUID", 4);
define("FILTER_TOKEN", 5);

  function SQL_limit($page, $count){
    if (!valid_int($page, 1)) throw new Exception("Invalid page number '".$page."'", E_FILTER_INVALID);
    if (!valid_int($count, 1, MAX_RECORDS_PER_PAGE)) throw new Exception("Invalid count per page '".$count."'", E_FILTER_INVALID);
      return " LIMIT ".($page - 1) * $count.",".$count;
  }

  function SQL_sort($sort_columns, $sort_by, $order){
    if (!in_array($sort_by, $sort_columns)) throw new Exception("Invalid sort column '".$sort_by."'", E_FILTER_INVALID);
    $order = strtoupper($order);
    if ($order != "ASC" && $order != "DESC") throw new Exception("Invalid sort order '".$order."'", E_FILTER_INVALID);
    return " ORDER BY ".quote_column($sort_by)." ".$order;
  }

  class SQLFilter{
    private $types = array();
    private $values = array();
    private $expressions = array();

    public function __construct($filter_defs, $filters){
      if (!is_array($filters)) throw new Exception("filters is not array", E_FILTER_INVALID);
      foreach ($filters as $filter) {
        $expression = "";

        if (!isset($filter["column"])) throw new Exception("Column is not set", E_FILTER_INVALID);
        if (!isset($filter["operator"])) throw new Exception("Operator is not set", E_FILTER_INVALID);
        if (!isset($filter["value"])) throw new Exception("Value is not set", E_FILTER_INVALID);
        $column = trim($filter["column"]);
        $operator = trim($filter["operator"]);
        $value = $filter["value"];
        $def = get_column_definition($column, $filter_defs);
        if (is_null($def)) throw new Exception("Invalid column '".$column."'", E_FILTER_INVALID);
        if (!in_array($operator, allowed_operators($def[2]))) throw new Exception("Invalid operator '".$operator."' for column '".$column."'", E_FILTER_INVALID);
        if ($def[2] == FILTER_BOOL){
          $value = trim($value);
          if ($value != 0 || strtoupper($value) == "TRUE" ||  strtoupper($value) == "YES" || strtoupper($value) == "ON"){
            $operator = "!=";
          }else{
            $operator = "=";
          }
          $value = 0;
        }
        switch ($operator){
          case "starting":
            $expression = "LIKE ".$def[1];
            $value = $value."%";
          break;
          case "!starting":
            $expression = "NOT LIKE ".$def[1];
            $value = $value."%";
          break;
          case "ending":
            $expression = "LIKE ".$def[1];
            $value = "%".$value;
          break;
          case "!starting":
            $expression = "NOT LIKE ".$def[1];
            $value = "%".$value;
          break;
          case "containing":
            $expression = "LIKE ".$def[1];
            $value = "%".$value."%";
          break;
          case "!containing":
            $expression = "NOT LIKE ".$def[1];
            $value = "%".$value."%";
          break;
          default: $expression = $operator." ".$def[1];
        }
        $expression = "(".quote_column($column)." ".$expression.")";
        switch ($def[2]){
          case FILTER_STR:
          case FILTER_UUID: array_push($this->types, "s"); break;
          case FILTER_INT:
          case FILTER_BOOL: array_push($this->types, "i"); break;
        }
        array_push($this->expressions, $expression);
        array_push($this->values, $value);
      }
    }

    public function get_types(){
      return $this->types;
    }

    public function get_values(){
      return $this->values;
    }

    public function get_search_expression(){
      $result = "";
      if (sizeof($this->expressions) > 0)
        $result = $this->expressions[0];
      for($i = 1; $i < sizeof($this->expressions); $i++){
        $result .= " AND ".$this->expressions[$i];
      }
      return $result;
    }

  }

  function get_column_definition(&$column, &$filter_defs){
    foreach ($filter_defs as $def) {
      if ($def[0] == $column) return $def;
    }
    return null;
  }

  function allowed_operators($filter_type){
    switch ($filter_type){
      case 1: return array("=", "!=", "starting", "!starting", "ending", "!ending", "containing", "!containing");
      case 2: return array("=", "!=", ">", "<", ">=", "<=");
      case 3: return array("=");
      case 4: return array("=", "!=");
      case 5: return array("=", "!=");
      default: throw new Exception("Invalid filter type", E_FIELD_INVALID);
    }
  }

  function quote_column($col){
    return "`".$col."`";
  }
?>
