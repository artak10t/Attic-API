<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("$root/v1/utils/errors.php");

class MYSQL{
  private $connection = null;
  private $in_transaction = false;
  private $connected = false;
  private $retry;
  private $max_retries;
  private $retry_delay;
  private $dlock;

  public function __construct(){
    $this->connection = new mysqli();
  }

  public function connect($address, $user, $pass, $db, $port){
    if ($this->connected)
      throw new Exception("MYSQL: Already connected", MYSQL_ERROR);
    @$this->connection->real_connect($address, $user, $pass, $db, $port);
    if ($this->connection->connect_errno){
      throw new Exception("MYSQL Connect: ".$this->connection->connect_error, $this->connection->connect_errno);
    }else{
      $this->connection->autocommit(false);
      $this->connected = true;
    }
  }

  public function disconnect(){
    if ($this->connected){
      $this->rollback();
      $this->connection->close();
    }
    $this->connected = false;
  }

  public function reset_deadlock($max_retries, $retry_delay){
    $this->max_retries = $max_retries;
    $this->retry_delay =$retry_delay;
    $this->retry = 0;
  }

  public function deadlock(){
    return $this->dlock;
  }

  public function start_transaction(){
    if (!$this->connected)
      throw new Exception("MYSQL: Not connected", MYSQL_ERROR);
    if (!$this->in_transaction){
      if (!$this->connection->begin_transaction())
        throw new Exception("MYSQL: ".$this->connection->error, $this->connection->errno);
      $this->dlock = false;
      $this->in_transaction = true;
    }
  }

  public function commit(){
    if ($this->in_transaction){
      if (!$this->connection->commit())
        throw new Exception("MYSQL: ".$this->connection->error, $this->connection->errno);
      $this->in_transaction = false;
    }
  }

  public function rollback(){
    if ($this->in_transaction){
      if (!$this->connection->rollback())
        throw new Exception("MYSQL: ".$this->connection->error, $this->connection->errno);
      $this->in_transaction = false;
    }
  }

  public function query($sql, array $binding_types = null, array $binding_values = null){
    if (!$this->in_transaction)
      throw new Exception("MYSQL: Transaction not started", MYSQL_ERROR);
    $stmt = $this->connection->prepare($sql);
    if (!$stmt){
      $err = $this->connection->error;
      $code = $this->connection->errno;
      $this->rollback();
      throw new Exception("MYSQL: ".$err, $code);
    }
    if (!empty($binding_values))
      if (!$stmt->bind_param(implode($binding_types), ...$binding_values)){
        $err = $stmt->error;
        $code = $stmt->errno;
        $stmt->close();
        $this->rollback();
        throw new Exception("MYSQL: ".$err, $code);
      }
    if($stmt->execute()){
      return $stmt;
    }else{
      if($stmt->errno == 1205 || $stmt->errno == 1213){
        $stmt->close();
        $this->rollback();
        $this->retry++;
        if ($this->retry >= $this->max_retries)
          throw new Exception("MYSQL: Maximum retries count for deadlocks reached", MYSQL_ERROR);
        usleep($this->retry_delay);
        $this->dlock = true;
        return null;
      }else{
        $err = $stmt->error;
        $code = $stmt->errno;
        $stmt->close();
        $this->rollback();
        throw new Exception("MYSQL: ".$err, $code);
      }
    }
  }

}

?>
