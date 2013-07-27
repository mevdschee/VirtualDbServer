<?php
class VirtualDbStatement implements Iterator {
  
  /*PDOStatement implements Traversable {

   ok readonly string $queryString;
      
      public bool bindColumn ( mixed $column , mixed &$param [, int $type [, int $maxlen [, mixed $driverdata ]]] )
   ok public bool bindParam ( mixed $parameter , mixed &$variable [, int $data_type = PDO::PARAM_STR [, int $length [, mixed $driver_options ]]] )
   ok public bool bindValue ( mixed $parameter , mixed $value [, int $data_type = PDO::PARAM_STR ] )
   ok public bool closeCursor ( void )
   ok public int columnCount ( void )
      public void debugDumpParams ( void )
      public string errorCode ( void )
      public array errorInfo ( void )
   ok public bool execute ([ array $input_parameters ] )
   ok public mixed fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
   ok public array fetchAll ([ int $fetch_style [, mixed $fetch_argument [, array $ctor_args = array() ]]] )
      public string fetchColumn ([ int $column_number = 0 ] )
      public mixed fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
   ok public mixed getAttribute ( int $attribute )
   ok public array getColumnMeta ( int $column )
      public bool nextRowset ( void )
   ok public int rowCount ( void )
   ok public bool setAttribute ( int $attribute , mixed $value )
      public bool setFetchMode ( int $mode )
  }*/
  
  public $queryString;
  
  private $position;
  private $rowCount;
  private $meta;
  private $array;
  private $db;
  private $ch;
  private $params;
  private $attributes;
  private $serverAttrs;
  
  public function __construct($queryString,$db,$ch,$attributes=array(),$serverAttrs=array()) {
    $this->queryString = $queryString;
    $this->position = 0;
    $this->db = $db;
    $this->ch = $ch;
    $this->params = array();
    $this->rowCount = false;
    $this->meta = array();
    $this->array = array();
    $this->attributes = $attributes;
    $this->serverAttrs = $serverAttrs;
  }
  
  public function execute ($input_parameters = array()) {
    $this->params = array_merge($this->params,$input_parameters);
//     echo(var_export($this->queryString,true));
//     echo(var_export($this->params,true));
    curl_setopt ($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->params));
    $headers = array();
    foreach ($this->attributes as $name=>$value) $headers[] = "X-Statement-$name: $value";
    foreach ($this->serverAttrs as $name=>$value) $headers[] = "X-Server-$name: $value";
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($this->ch);
    $this->array = explode("\n",$result);
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
    return true;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function fetch($type = PDO::FETCH_ASSOC)
  {
    $data = json_decode($this->current(),$type != PDO::FETCH_OBJ);
    if ($data===null) return false;
    $this->next();
    $hash = array();
    if ($type == PDO::FETCH_NUM) $data = $data;
    else foreach ($this->meta as $i=>$meta) $hash[$meta->name]=$data[$i];
    if ($type == PDO::FETCH_BOTH) $data = array_merge($data,$hash);
    if ($type == PDO::FETCH_ASSOC) $data = $hash;
    if ($type == PDO::FETCH_LAZY) $data = array_merge($data,$hash);
    if ($type == PDO::FETCH_OBJ) $data = (object)$hash;
    //if (count($data)>5) die(var_export(array(PDO::FETCH_OBJ,$type,$data),true));
    return $data;
  }
  
  public function fetchAll($type = PDO::FETCH_NUM)
  {
    $data = array();
    while (false !== ($row = $this->fetch($type))) {
      $data[]=$row;
    }
    return $data;
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
    return count($this->array);
  }

  public function columnCount() {
    return count($this->meta);
  }

  public function getColumnMeta($index) {
    return (array)$this->meta[$index];
  }
  
  public function setFetchMode($mode) {
    return true;
  }

  public function getAttribute($index) {
    return $this->attributes[$index];
  }
  
  public function setAttribute($index,$value) {
    $this->attributes[$index] = $value;
  }
  
}

class VirtualDbServer
{
  /* PDO {
      public __construct ( string $dsn [, string $username [, string $password [, array $driver_options ]]] )
      public bool beginTransaction ( void )
      public bool commit ( void )
      public mixed errorCode ( void )
      public array errorInfo ( void )
   ok public int exec ( string $statement )
   ok public mixed getAttribute ( int $attribute )
   ok public static array getAvailableDrivers ( void )
      public bool inTransaction ( void )
   ok public string lastInsertId ([ string $name = NULL ] )
      public PDOStatement prepare ( string $statement [, array $driver_options = array() ] )
      public PDOStatement query ( string $statement )
   ok public string quote ( string $string [, int $parameter_type = PDO::PARAM_STR ] )
      public bool rollBack ( void )
   ok public bool setAttribute ( int $attribute , mixed $value )
   }*/
  
  private $ch;
  private $url;
  private $dbname;
  private $lastInsertId;
  private $attributes;
  private $settings;
  
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
    $this->lastInsertId = false;
    $this->attributes = $attributes;
  }
  
  public function setLastInsertId($val) {
    $this->lastInsertId = $val;
  }
  
  public function lastInsertId() {
    return $this->lastInsertId;
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
    if(!empty($str) && is_string($str)) {
      $search = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
      $replace = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
      return $startQuote.str_replace($search,$replace,$str).$endQuote;
    }
    return $str;
  }
  
  public function prepare($statement,$attributes = array()) {
    curl_setopt ($this->ch, CURLOPT_URL, $this->url.urlencode($statement));
    return new VirtualDbStatement($statement, $this, $this->ch, $attributes, $this->attributes);
  }
  
  public function exec($statement) {
    $statement = $this->prepare($statement);
    return $statement->execute();
  }
  
  public function getAttribute($index) {
    return $this->attributes[$index];
  }
  
  public function setAttribute($index,$value) {
    $this->attributes[$index] = $value;
  }
  
}