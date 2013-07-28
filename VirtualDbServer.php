<?php
class VirtualDbRow {
  
  public $queryString;
  
  public function __construct($queryString) {
    $this->queryString = $queryString;
  }
}

class VirtualDbStatement implements Iterator {
  
  /*PDOStatement implements Traversable {

   ok readonly string $queryString;
      
   ok public bool bindColumn ( mixed $column , mixed &$param [, int $type [, int $maxlen [, mixed $driverdata ]]] )
   ok public bool bindParam ( mixed $parameter , mixed &$variable [, int $data_type = PDO::PARAM_STR [, int $length [, mixed $driver_options ]]] )
   ok public bool bindValue ( mixed $parameter , mixed $value [, int $data_type = PDO::PARAM_STR ] )
   ok public bool closeCursor ( void )
   ok public int columnCount ( void )
      public void debugDumpParams ( void )
   ok public string errorCode ( void )
   ok public array errorInfo ( void )
   ok public bool execute ([ array $input_parameters ] )
   ok public mixed fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
   ok public array fetchAll ([ int $fetch_style [, mixed $fetch_argument [, array $ctor_args = array() ]]] )
   ok public string fetchColumn ([ int $column_number = 0 ] )
   ok public mixed fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
   ok public mixed getAttribute ( int $attribute )
   ok public array getColumnMeta ( int $column )
      public bool nextRowset ( void )
   ok public int rowCount ( void )
   ok public bool setAttribute ( int $attribute , mixed $value )
   ok public bool setFetchMode ( int $mode )
  }*/
  
  public $queryString;

  private $position;
  private $rowCount;
  private $errorCode;
  private $errorInfo;
  private $meta;
  private $array;
  private $db;
  private $ch;
  private $columns;
  private $params;
  private $attributes;
  private $serverAttrs;
  private $fetchMode;
  private $fetchArgument;
  private $constructorArguments;
  
  public function __construct($queryString,$db,$ch,$attributes=array(),$serverAttrs=array()) {
    $this->queryString = $queryString;
    $this->position = 0;
    $this->rowCount = false;
    $this->errorCode = '';
    $this->errorInfo = array();
    $this->meta = array();
    $this->array = array();
    $this->db = $db;
    $this->ch = $ch;
    $this->columns = array();
    $this->params = array();
    $this->attributes = array();
    foreach($attributes as $index => $value) {
      $this->setAttribute($index,$value);
    }
    $this->serverAttrs = $serverAttrs;
    $this->fetchMode = false;
    $this->fetchArgument = false;
    $this->constructorArguments = array();
  }
  
  public function execute ($input_parameters = false) {
    if ($input_parameters!==false) $this->params = $input_parameters;
//     echo(var_export($this->queryString,true));
//     echo(var_export($this->params,true));
    curl_setopt ($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->params));
    $headers = array();
    foreach ($this->attributes as $name=>$value) $headers[] = "X-Statement-$name: $value";
    foreach ($this->serverAttrs as $name=>$value) $headers[] = "X-Server-$name: $value";
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($this->ch);
    $this->position = 0;
    $this->array = explode("\n",$result);
    $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    if ($status==200) {
      $this->errorCode = '0000';
      $this->errorInfo = array();
      $this->rowCount = json_decode(array_shift($this->array));
      $this->db->setLastInsertId(json_decode(array_shift($this->array)));
      $this->meta = json_decode(array_shift($this->array));
  //     var_dump($this->queryString);
  //     if (preg_match('/SELECT.*Customer/',$this->queryString))
  //     if ($this->rowCount==22)
  //      {
  //        die(var_export($result,true));
  //        die(var_export($this->queryString,true).var_export($result,true));
  //      }
      $result = true;
    } elseif ($status==400) {
      $this->errorCode = json_decode(array_shift($this->array));
      $this->errorInfo = json_decode(array_shift($this->array));
      $result = false;
    }
    return $result;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function fetch($type = false)
  {
    if ($type===false) $type = $this->fetchMode;
    if ($type===false) $type = PDO::FETCH_BOTH;
    $data = json_decode($this->current(),true);
    if ($data===null) return false;
    $this->next();
    $result = array();
    if ($type == PDO::FETCH_ASSOC) {
      foreach ($this->meta as $i=>$meta) {
        $result[$meta->name]=$data[$i];
      }
    }
    if ($type == PDO::FETCH_BOTH){
      foreach ($this->meta as $i=>$meta) {
        $result[$meta->name]=$data[$i];
        $result[$i]=$data[$i];
      }
    }
    if ($type == PDO::FETCH_BOUND) {
      foreach ($this->meta as $i=>$meta) {
        $result[$meta->name]=$data[$i];
        $result[$i+1]=$data[$i];
      }
      $columns = array_keys($this->columns);
      foreach ($columns as $column) {
        $this->columns[$column]=$result[$column];
      }
      $result = true;
    }
    if ($type == PDO::FETCH_CLASS) {
      $reflect = new ReflectionClass($this->fetchArgument);
      $result = $reflect->newInstanceArgs($this->constructorArguments);
      foreach ($this->meta as $i=>$meta) {
        $property = $meta->name;
        $result->$property=$data[$i];
      }
    }
    if ($type == (PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE)) {
      $reflect = new ReflectionClass($data[0]);
      $result = $reflect->newInstanceArgs($this->constructorArguments);
      foreach ($this->meta as $i=>$meta) {
        if ($i>0) {
          $property = $meta->name;
          $result->$property=$data[$i];
        }
      }
    }
    if ($type == PDO::FETCH_INTO) {
      $result =& $this->fetchArgument;
      foreach ($this->meta as $i=>$meta) {
        $property = $meta->name;
        $result->$property=$data[$i];
      }
    }
    if ($type == PDO::FETCH_LAZY) {
      $result = new VirtualDbRow($this->queryString);
      foreach ($this->meta as $i=>$meta) {
        $property = $meta->name;
        $result->$property=$data[$i];
      }
    }
    if ($type == PDO::FETCH_NUM) {
      $result = $data;
    }
    if ($type == PDO::FETCH_OBJ) {
      foreach ($this->meta as $i=>$meta) {
        $result[$meta->name]=$data[$i];
      }
      $result = (object)$result;
    }
    //if (count($data)>5) die(var_export(array(PDO::FETCH_OBJ,$type,$data),true));
    return $result;
  }
  
  public function fetchColumn($column = 0) {
    $data = json_decode($this->current(),true);
    $this->next();
    return $data[$column];
  }
  
  public function fetchObject($argument = "stdClass", $constructorArguments = array()) {
    $this->setFetchMode(PDO::FETCH_CLASS,$argument,$constructorArguments);
    return $this->fetch();
  }
  
  public function fetchAll($type = false, $argument = false,$constructorArguments = array())
  {
    if ($type===false) $type = $this->fetchMode;
    if ($type===false) $type = PDO::FETCH_BOTH;
    $this->setFetchMode($type,$argument,$constructorArguments);
    $data = array();
    while (false !== ($row = $this->fetch())) {
      $data[]=$row;
    }
    return $data;
  }

  public function bindColumn($column, &$param) {
    $this->columns[$column] =& $param;
  }
  
  public function bindParam($parameter, &$variable) {
    $this->params[$parameter] =& $variable;
  }
  
  public function bindValue($parameter, $variable) {
    $this->params[$parameter] = $variable;
  }
  
  public function current() {
    return isset($this->array[$this->position])?$this->array[$this->position]:false;
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function valid() {
    return isset($this->array[$this->position]);
  }

  public function closeCursor() {
    return true;
  }

  public function rowCount() {
    return $this->rowCount;
  }

  public function columnCount() {
    return count($this->meta);
  }
  
  public function errorCode() {
    return $this->errorCode;
  }
  
  public function errorInfo() {
    return $this->errorInfo;
  }
  
  public function getColumnMeta($index) {
    return (array)$this->meta[$index];
  }
  
  public function getAttribute($index) {
    if ($index == PDO::ATTR_DEFAULT_FETCH_MODE) {
      $result = $this->fetchMode;
    } else {
      $result = $this->attributes[$index];
    }
    return $result;
  }
  
  public function setAttribute($index,$value) {
    if ($index == PDO::ATTR_DEFAULT_FETCH_MODE) {
      $this->fetchMode = $value;
    } else {
      $this->attributes[$index] = $value;
    }
  }
  
  public function setFetchMode($type,$argument=false,$constructorArguments = array()) {
    $this->fetchMode = $type;
    $this->fetchArgument =& $argument;
    $this->constructorArguments =& $constructorArguments;
  }
}

class VirtualDbServer
{
  /* PDO {
   ok public __construct ( string $dsn [, string $username [, string $password [, array $driver_options ]]] )
      public bool beginTransaction ( void )
      public bool commit ( void )
   ok public mixed errorCode ( void )
   ok public array errorInfo ( void )
   ok public int exec ( string $statement )
   ok public mixed getAttribute ( int $attribute )
   ok public static array getAvailableDrivers ( void )
      public bool inTransaction ( void )
   ok public string lastInsertId ([ string $name = NULL ] )
   ok public PDOStatement prepare ( string $statement [, array $driver_options = array() ] )
   ok public PDOStatement query ( string $statement )
   ok public string quote ( string $string [, int $parameter_type = PDO::PARAM_STR ] )
      public bool rollBack ( void )
   ok public bool setAttribute ( int $attribute , mixed $value )
   }*/
  
  private $ch;
  private $url;
  private $dbname;
  private $lastStatement;
  private $lastInsertId;
  private $attributes;
  
  public function __construct($dsn, $username = false, $password = false, $attributes = array()) {
    $this->ch = curl_init();
    curl_setopt ($this->ch, CURLOPT_POST, true);
    curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt ($this->ch, CURLOPT_COOKIEJAR, '/dev/null');
    curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, true);
    list($driver,$string) = explode(':',$dsn,2);
    $parameters = array();
    $params = explode(';',$string);
    foreach ($params as $param) {
      list($key,$value) = explode('=',$param,2);
      $parameters[$key] = $value;
    }
    $this->url = $parameters['host'];
    $this->dbname = $parameters['dbname'];
    $this->lastStatement = false;
    $this->lastInsertId = false;
    $this->attributes = $attributes;
  }
  
  public function setLastInsertId($value) {
    return $this->lastInsertId=$value;
  }  
  
  public function lastInsertId() {
    return $this->lastInsertId;
  }
  
  public function errorCode() {
    return $this->lastStatement->errorCode();
  }
  
  public function errorInfo() {
    return $this->lastStatement->errorInfo();
  }
  
  static function getAvailableDrivers() {
    return array('mysql');
  }
  
  public function quote($str) {
    // is this safe when server and client character-set are utf8?
    $startQuote = '\'';
    $endQuote = '\'';
    if(is_array($str))
      return array_map(__METHOD__, $str);
    if(is_string($str)) {
      $search = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
      $replace = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
      return $startQuote.str_replace($search,$replace,$str).$endQuote;
    }
    return $str;
  }
  
  public function prepare($statement,$attributes = array()) {
    $this->attributes = array_merge($this->attributes,$attributes);
    curl_setopt ($this->ch, CURLOPT_URL, $this->url.urlencode($statement));
    $this->lastStatement = new VirtualDbStatement($statement, $this, $this->ch, $attributes, $this->attributes);
    return $this->lastStatement;
  }
  
  public function exec($statement) {
    $this->lastStatement = $this->prepare($statement);
    $this->lastStatement->execute();
    return $this->lastStatement->rowCount();
  }
  
  public function query($statement) {
    $this->lastStatement = $this->prepare($statement);
    $this->lastStatement->execute();
    return $this->lastStatement;
  }
  
  public function getAttribute($index) {
    return $this->attributes[$index];
  }
  
  public function setAttribute($index,$value) {
    $this->attributes[$index] = $value;
  }
  
}