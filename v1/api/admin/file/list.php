<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/config.php");
include_once("$root/v1/utils/mysql.php");
include_once("$root/v1/utils/http.php");
include_once("$root/v1/utils/errors.php");
include_once("$root/v1/utils/validations.php");
include_once("$root/v1/utils/sql_builder.php");

$mysql = new MYSQL();
try{
  try{
    if($_SERVER["REQUEST_METHOD"] != "POST") throw new Exception("Only POST method allowed", E_ONLY_POST);
    $post = decode_post();

    if(!isset($post['session_id'])) throw new Exception("session_id field is not set", E_FIELD_NOT_SET);
    if(!isset($post['folder_id'])) throw new Exception("folder_id field is not set", E_FIELD_NOT_SET);
    $session_id = trim($post['session_id']);
    if(!valid_str($session_id, 64, 64)) throw new Exception("session_id field is not valid", E_FIELD_INVALID);
    $folder_id = trim($post['folder_id']);
    if(!valid_str($folder_id, 36, 36)) throw new Exception("folder_id field is not valid", E_FIELD_INVALID);

    $filters = array();
    if(isset($post['filters'])) $filters = $post['filters'];
    $sort_by = "name"; // default sort column
    if(isset($post['sort_by'])) $sort_by = trim($post['sort_by']);
    $order = "ASC"; // default sort order
    if(isset($post['order'])) $order = trim($post['order']);
    $count = MAX_RECORDS_PER_PAGE;
    if(isset($post['count'])) $count = trim($post['count']);
    $page = 1;
    if(isset($post['page'])) $page = trim($post['page']);
    $control_pages_count = MAX_CONTROL_PAGES_COUNT;
    if(isset($post['control_pages_count'])) $control_pages_count = trim($post['control_pages_count']);
    if(!valid_int($control_pages_count, 1, MAX_CONTROL_PAGES_COUNT)) throw new Exception("control_pages_count field is not valid", E_FIELD_INVALID);

    $mysql->connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // check if session_id is valid, user is admin and account is enabled
      $stmt = $mysql->query('SELECT `admin`, `disabled`, `reason` FROM `v_sessions` WHERE `session_id` = token_to_bin(?)', array("s"), array($session_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_assoc()) throw new Exception("Invalid session", E_NOT_LOGGED_IN);
      if (!$arr["admin"]) throw new Exception("Not authorized", E_UNAUTHORIZED);
      if ($arr["disabled"]) throw new Exception("Account disabled: ".$arr["reason"], E_DISABLED);
      $stmt->close();

      // check if folder_id is valid
      $stmt = $mysql->query('SELECT 0 FROM `folders` WHERE `folder_id` = uuid_to_bin(?)', array("s"), array($folder_id));
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      if (!$arr = $result->fetch_array()) throw new Exception("Folder doesn't exist", E_DOESNT_EXIST);
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    $filter_defs = array(
      array("account_id", "uuid_to_bin(?)", FILTER_UUID),
      array("account_name", "?", FILTER_STR),
      array("folder_id", "uuid_to_bin(?)", FILTER_UUID),
      array("folder_name", "?", FILTER_STR),
      array("file_id", "uuid_to_bin(?)", FILTER_UUID),
      array("name", "?", FILTER_STR),
      array("public", "?", FILTER_BOOL),
      array("size", "?", FILTER_INT),
      array("current_size", "?", FILTER_INT),
      array("description", "?", FILTER_STR)
    );

    $sort_columns = array(
      "file_id",
      "name",
      "public",
      "size",
      "current_size"
    );


    $f = new SQLFilter($filter_defs, $filters);
    $sort = SQL_sort($sort_columns, $sort_by, $order);
    $limit = SQL_limit($page, $count);

    $expression = $f->get_search_expression();

    $sql = "SELECT bin_to_uuid(`account_id`) AS `account_id`, `account_name`, bin_to_uuid(`folder_id`) AS `folder_id`, `folder_name`, ".
            " bin_to_uuid(`file_id`) AS `file_id`, `name`, `public`, `size`, `current_size`, `description` FROM `v_files` WHERE `folder_id` = uuid_to_bin(?)";
    $ctrl_sql = "SELECT COUNT(0) FROM (SELECT 0 FROM `v_files` WHERE `folder_id` = uuid_to_bin(?)";
    if ($expression != ""){
      $sql .= " AND ".$expression;
      $ctrl_sql .= " AND ".$expression;
    }
    $ctrl_sql .= " LIMIT " . (($page - 1) * $count) . "," . ($control_pages_count * $count + 1) . ") AS `alias`";
    $sql .= $sort;
    $sql .= $limit;

    $types = $f->get_types();
    $values = $f->get_values();
    array_unshift($types, "s");
    array_unshift($values, $folder_id);

    $mysql->reset_deadlock(DEADLOCK_RETRIES, DEADLOCK_SLEEP);
    do{
      $mysql->start_transaction();
      // examine control page count
      $stmt = $mysql->query($ctrl_sql, $types, $values);
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $ctrl_count = $result->fetch_array()[0];
      $stmt->close();

      $pages_available = ceil($ctrl_count / $count);
      $more_pages_available = 0;
      if ($pages_available > $control_pages_count){
        $pages_available = $control_pages_count;
        $more_pages_available = 1;
      }

      $data = array();
      $data["more_pages_available"] = $more_pages_available;
      $data["pages_available"] = $pages_available;

      $stmt = $mysql->query($sql, $types, $values);
      if ($mysql->deadlock()) continue;
      $result = $stmt->get_result();
      $data["files"] = array();
      while($arr = $result->fetch_assoc()){
        $files = array();
        $files["account_id"] = $arr["account_id"];
        $files["account_name"] = $arr["account_name"];
        $files["folder_id"] = $arr["folder_id"];
        $files["folder_name"] = $arr["folder_name"];
        $files["file_id"] = $arr["file_id"];
        $files["name"] = $arr["name"];
        $files["public"] = $arr["public"];
        $files["size"] = $arr["size"];
        $files["current_size"] = $arr["current_size"];
        $files["description"] = $arr["description"];

        array_push($data["files"], $files);
      }
      $stmt->close();
    } while ($mysql->deadlock());
    $mysql->commit();

    send_response(SUCCESS, "", $data);
  }catch(Exception $e){
    send_response($e->getCode(), $e->getMessage());
  }
}finally{
  $mysql->disconnect();
}
?>
