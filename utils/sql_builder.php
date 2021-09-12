<?php
/*
  public variables:

    $binding_values
      # array - variable values to pass to mysqli::bind_params statement
      # use call_user_func_array to evaluate this array into mysqli::bind_params

    $binding_types ;
      # array - variable types to pass to mysqli bind_params statement


  public functions:

  constructor($table, array $column_defs, array $column_types)
    # $table - string, table name
    # $column_defs - array, all column names used in sql generation
    # $column_types - char, valid types = 's', 'i', 'd', 'b'

  select(array $columns)
    # $columns - array, all column names to be selected
    # returns SQLBuilder object or raise an exception

  count()
    # Used to get count of records
    # returns SQLBuilder object or raise an exception

  update(array $columns, array $values)
    # $columns - array, all column names to be updated
    # $columns - array, values to update
    # returns SQLBuilder object or raise an exception

  where(array $columns, array $operators, array $values)
    # $columns - array, all column names in search condition
    # $operators - array, available operators: '=', '!=', '<>', '>', '<', '>=', '<=', '~', '!~', 'NULL', '!NULL'
    # $values - array, values to be searched
    # Multiple filters are merged by logical AND
    # returns SQLBuilder object or raise an exception

  order($column, $order = 'ASC')
    # $column - string, field name to use in Ordering
    # $order - string, "ASC" or "DESC"
    # Leave empty to disable ordering and fetch records in database order
    # returns SQLBuilder object or raise an exception

  limit($page, $count)
    # $page - int, start page, first page = 1
    # $count - int, how many records to fetch
    # Leave empty to fetch all records
    # returns SQLBuilder object or raise an exception

  sql()
    # Returns SQL expression to get records count from selected table or raise an exception on failure

*/

/* example
try{
$b = new SQLBuilder('', array('aaa','bbb','ccc'), array('s', 'i', 's'));
} catch(Exception $e){
  print $e->getMessage()."<br>".$e->getCode();
}
/*echo $b->select(array('aaa', 'bbb'))->where(array('aaa', 'ccc'), array('=', 'null'), array('sdsdfdsfds', 'sdfsdfsdf'))->limit(2,10)->order('ccc', 'DESC')->sql()."<br>";
//echo $b->count()->where(array('aaa', 'ccc'), array('=', 'null'), array('sdsdfdsfds', 'sdfsdfsdf'))->limit(2,10)->order('ccc', 'DESC')->sql();
//echo $b->update(array('aaa', 'bbb'), array(1, null))->where(array('aaa', 'ccc'), array('=', 'null'), array('sdsdfdsfds', 'sdfsdfsdf'))->sql()."<br>";
var_dump($b->binding_types)."<br>";
var_dump($b->binding_values);
*/

class SQLBuilder
{
  private $mode = -1; // modes: 0=select, 1=count, 2=update
  private const TYPES = array('s', 'i', 'd', 'b');
  private const OPERATORS = array('=', '!=', '<>', '>', '<', '>=', '<=', '~', '!~', 'null', '!null');
  private $table = null;
  private $column_types = null;
  private $column_defs = null;

  private $columns = null;
  private $values = null;
  private $where_columns = null;
  private $where_operators = null;
  private $where_values = null;
  private $order_column = null;
  private $order = null;
  private $limit = null;

  public $binding_values = null;
  public $binding_types = null;

  function __construct($table, array $column_defs, array $column_types)
  {
    if (empty($table))
      throw new Exception('SQLBuilder: Table name is empty', -1000);

    if (empty($column_defs))
      throw new Exception('SQLBuilder: Column Definitions array is empty', -1000);

    if (empty($column_types))
      throw new Exception('SQLBuilder: Column Types array is empty', -1000);

    if ($this->find_duplicate($column_defs, $dupe))
      throw new Exception('SQLBuilder: Duplicate Column Definition: '.$dupe, -1000);

    foreach ($column_types as $t)
    {
      if (!in_array($t, $this::TYPES))
        throw new Exception('SQLBuilder: Invalid Column Definition Type: '.$t, -1000);
    }

    if (count($column_types) != count($column_defs))
      throw new Exception('SQLBuilder: Column Definitions and Types count missmatch', -1000);

    $this->table = $table;
    $this->column_types = $column_types;
    $this->column_defs = $column_defs;
  }

  public function select(array $columns)
  {
    $this->reset(0);
    if(empty($columns))
      throw new Exception('SQLBuilder: SELECT Columns are null or empty', -1000);

    if ($this->find_duplicate($columns, $dupe))
      throw new Exception('SQLBuilder: Duplicate SELECT Column: '.$dupe, -1000);

    foreach($columns as $col)
    {
      if (!in_array($col, $this->column_defs))
        throw new Exception('SQLBuilder: Undefined SELECT Column: '.$col, -1000);
    }

    $this->columns = $columns;
    return $this;
  }

  public function count()
  {
    $this->reset(1);
    return $this;
  }

  public function update(array $columns, array $values)
  {
    $this->reset(2);
    if(empty($columns))
      throw new Exception('SQLBuilder: UPDATE Columns are null or empty', -1000);

    foreach($columns as $col)
    {
      if (!in_array($col, $this->column_defs))
        throw new Exception('SQLBuilder: Undefined UPDATE Column: '.$col, -1000);
    }

    if (count($columns) != count($values))
      throw new Exception('SQLBuilder: UPDATE Columns and Values count missmatch', -1000);

    $this->columns = $columns;
    $this->values = $values;
    return $this;
  }

  public function where(array $columns, array $operators, array $values)
  {
    if ($this->mode < 0)
      throw new Exception("SQLBuilder: Statement not initialized. Please call 'select', 'count' or 'update' before 'where'", -1000);

    if(empty($columns))
      throw new Exception('SQLBuilder: WHERE Columns are null or empty', -1000);

    foreach($columns as $col)
    {
      if (!in_array($col, $this->column_defs))
        throw new Exception('SQLBuilder: Undefined WHERE Column: '.$col, -1000);
    }

    foreach ($operators as $o)
    {
      if (!in_array($o, $this::OPERATORS))
        throw new Exception('SQLBuilder: Invalid Operator: '.$o, -1000);
    }

    if (count($columns) != count($operators))
      throw new Exception('SQLBuilder: WHERE Columns and Operators count missmatch', -1000);

    if (count($columns) != count($values))
      throw new Exception('SQLBuilder: WHERE Columns and Values count missmatch', -1000);

    $this->where_columns = $columns;
    $this->where_operators = $operators;
    $this->where_values = $values;
    return $this;
  }

  public function order($column, $order = 'ASC')
  {
    if ($this->mode < 0)
      throw new Exception("SQLBuilder: Statement not initialized. Please call 'select', 'count' or 'update' before 'order'", -1000);

    if(empty($column))
      throw new Exception('SQLBuilder: ORDER Columns is null or empty', -1000);

    $order = strtoupper($order);
    if ($order !== 'ASC' && $order !== 'DESC')
      throw new Exception("SQLBuilder: ORDER must be 'ASC' or 'DESC'", -1000);

    if (!in_array($column, $this->column_defs))
      throw new Exception('SQLBuilder: Undefined ORDER Column: '.$column, -1000);

    $this->order_column = $column;
    $this->order = $order;
    return $this;
  }

  public function limit($page, $count)
  {
    if ($this->mode < 0)
      throw new Exception("SQLBuilder: Statement not initialized. Please call 'select', 'count' or 'update' before 'limit'", -1000);

    if ($page <= 0)
      throw new Exception('SQLBuilder: Page for LIMIT is negative: '.$page, -1000);

    if ($count <= 0)
      throw new Exception('SQLBuilder: Count for LIMIT is negative: '.$count, -1000);

    $this->limit = ($page - 1) * $count.','.$count;
    return $this;
  }

  public function sql()
  {
    $this->binding_types = array();
    $this->binding_values = array();
    switch ($this->mode)
    {
      case 0:
        // SELECT
        $columns_text = '';
        for ($i = 0; $i < count($this->columns); $i++)
        {
          $col = $this->columns[$i];
          if ($i == 0)
          {
            $columns_text = $this->backtick($col);
          }
          else
          {
            $columns_text = $columns_text.', '.$this->backtick($col);
          }
        }

        $filters_text = $this->get_filters_text();

        $order_text = '';
        if (!is_null($this->order_column))
          $order_text = $this->backtick($this->order_column).' '.$this->order;

        $sql = "SELECT ".$columns_text." FROM ".$this->backtick($this->table);
        if ($filters_text !== '')
          $sql = $sql." WHERE ".$filters_text;

        if ($order_text !== '')
          $sql = $sql." ORDER BY ".$order_text;

        if (!is_null($this->limit))
          $sql = $sql." LIMIT ".$this->limit;

        return $sql;
      case 1:
        // COUNT
        $filters_text = $this->get_filters_text();
        $sql = "SELECT COUNT(0) FROM ".$this->backtick($this->table);
        if ($filters_text !== '')
          $sql = $sql." WHERE ".$filters_text;

        return $sql;
      case 2:
        // UPDATE
        $columns_text = '';
        for ($i = 0; $i < count($this->columns); $i++)
        {
          $col = $this->columns[$i];
          $val = $this->values[$i];
          $this->add_binding($this->get_binding_type($col), $val);
          if ($i == 0)
          {
            $columns_text = $this->backtick($col).' = ?';
          }
          else
          {
            $columns_text = $columns_text.', '.$this->backtick($col).' = ?';
          }
        }

        $filters_text = $this->get_filters_text();

        $sql = 'UPDATE '.$this->backtick($this->table).' SET '.$columns_text;
        if ($filters_text !== '')
          $sql = $sql." WHERE ".$filters_text;

        return $sql;

      default:
        throw new Exception("SQLBuilder: Statement not initialized. Please call 'select', 'count' or 'update' before 'sql'", -1000);
        break;
    }
  }

  private function get_filters_text()
  {
    $filters_text = '';
    if(is_null($this->where_columns))
      return $filters_text;

    for ($i = 0; $i < count($this->where_columns); $i++)
    {
      $col = $this->where_columns[$i];
      $op = $this->where_operators[$i];
      $val = $this->where_values[$i];
      if ($op === 'null')
      {
        $op = 'IS NULL';
      }
      else if ($op === '!null')
      {
        $op = 'IS NOT NULL';
      }
      else if ($op === '~')
      {
        $op = "LIKE CONCAT('%', ?, '%')";
        $this->add_binding($this->get_binding_type($col), $val);
      }
      else if ($op === '!~')
      {
        $op = "NOT LIKE CONCAT('%', ?, '%')";
        $this->add_binding($this->get_binding_type($col), $val);
      }
      else
      {
        $op = $op." ?";
        $this->add_binding($this->get_binding_type($col), $val);
      }
      if ($i == 0)
      {
        $filters_text = '('.$this->backtick($col).' '.$op.')';
      }
      else
      {
        $filters_text = $filters_text.' AND '.'('.$this->backtick($col).' '.$op.')';
      }
    }
    return $filters_text;
  }

  private function reset($mode)
  {
    $this->mode = $mode;
    $this->where_columns = null;
    $this->where_operators = null;
    $this->where_values = null;
    $this->order_column = null;
    $this->order = null;
    $this->limit = null;
  }

  private function add_binding($type, $value)
  {
    array_push($this->binding_types, $type);
    array_push($this->binding_values, $value);
  }

  private function get_binding_type($column)
  {
    for ($i = 0; $i < count($this->column_defs); $i++)
    {
      if ($this->column_defs[$i] === $column)
        return $this->column_types[$i];
    }
    return false;
  }

  private function backtick($str)
  {
    return '`'.$str.'`';
  }

  private function find_duplicate(array $array, &$dupe_value)
  {
      $dupe_array = array();
      foreach ($array as $val)
      {
        if(in_array($val, $dupe_array))
        {
          $dupe_value = $val;
          return true;
        }
        $dupe_array[] = $val;
      }
      return false;
  }
}
?>
