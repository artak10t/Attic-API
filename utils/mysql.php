<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/config/config.php");

class MYSQL
{
  private static $internal_connection = null;
  private static $stmt = null;

  public static function connect()
  {
    if (is_null(self::$internal_connection))
    {
      self::$internal_connection = @new mysqli(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);

      if (self::$internal_connection->connect_errno)
      {
        throw new Exception("MYSQL Connect: ".self::$internal_connection->connect_error, self::$internal_connection->connect_errno);
        self::$internal_connection = null;
      }
      else
      {
        self::$internal_connection->autocommit(false);
      }
    }
  }

  public static function start_transaction()
  {
    if (!self::$internal_connection->begin_transaction())
      throw new Exception("MYSQL: ".self::$internal_connection->error, self::$internal_connection->errno);
  }

  public static function commit()
  {
    if (!self::$internal_connection->commit())
      throw new Exception("MYSQL: ".self::$internal_connection->error, self::$internal_connection->errno);
  }

  public static function stmt_result()
  {
    $result = self::$stmt->get_result();
    if(!$result)
      throw new Exception("MYSQL: ".self::$stmt->error, self::$stmt->errno);

    return $result;
  }

  public static function stmt_close()
  {
    if(self::$stmt && !self::$stmt->close())
      throw new Exception("MYSQL: ".self::$stmt->error, self::$stmt->errno);
  }

  public static function rollback()
  {
    if(self::$stmt && !self::$internal_connection->rollback())
      throw new Exception("MYSQL: ".self::$internal_connection->error, self::$internal_connection->errno);
  }

  public static function query($sql, array $binding_types = null, array $binding_values = null){
    if(self::$stmt)
      self::$stmt->close();

    $retry = 0;
    while ($retry < DEADLOCK_RETRIES)
    {
      self::$stmt = self::$internal_connection->prepare($sql);
      if (!self::$stmt)
        throw new Exception("MYSQL: ".self::$internal_connection->error, self::$internal_connection->errno);

      if (!empty($binding_values))
        if (!self::$stmt->bind_param(implode($binding_types), ...$binding_values))
          throw new Exception("MYSQL: ".self::$stmt->error, self::$stmt->errno);

      if(self::$stmt->execute())
      {
        return;
      }
      else
      {
        if(self::$stmt->errno == 1614 || self::$stmt->errno == 1213)
        {
          self::$stmt->close();
          self::$connection->rollback();
          usleep(DEADLOCK_SLEEP);
          $retry++;
        }
        else
        {
          throw new Exception("MYSQL: ".self::$stmt->error, self::$stmt->errno);
        }
      }
    }
    throw new Exception("MYSQL: Maximum retries count for deadlocks reached", -1001);
  }
}
?>
