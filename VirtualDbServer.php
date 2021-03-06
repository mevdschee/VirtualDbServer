<?php
class VirtualDbRow /* extends PDORow */ {
  
  public $queryString;
  
  public function __construct($queryString) {
    $this->queryString = $queryString;
  }
}

class VirtualDbStatement /* extends PDOStatement */ implements Iterator {
  
  /*PDOStatement implements Traversable {

   done readonly string $queryString;
      
   args public bool bindColumn ( mixed $column , mixed &$param [, int $type [, int $maxlen [, mixed $driverdata ]]] )
   args public bool bindParam ( mixed $parameter , mixed &$variable [, int $data_type = PDO::PARAM_STR [, int $length [, mixed $driver_options ]]] )
   args public bool bindValue ( mixed $parameter , mixed $value [, int $data_type = PDO::PARAM_STR ] )
   done public bool closeCursor ( void )
   done public int columnCount ( void )
   done public void debugDumpParams ( void )
   done public string errorCode ( void )
   done public array errorInfo ( void )
   done public bool execute ([ array $input_parameters ] )
   args public mixed fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
   done public array fetchAll ([ int $fetch_style [, mixed $fetch_argument [, array $ctor_args = array() ]]] )
   done public string fetchColumn ([ int $column_number = 0 ] )
   done public mixed fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
   done public mixed getAttribute ( int $attribute )
   done public array getColumnMeta ( int $column )
        public bool nextRowset ( void )
   done public int rowCount ( void )
   done public bool setAttribute ( int $attribute , mixed $value )
   done public bool setFetchMode ( int $mode )
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
  
  private function decodeStrings(&$str) {
    if (is_array($str)) return array_map(__METHOD__, $str);
    if (is_string($str)) return base64_decode($str);
    return $str;
  }
  
  public function execute ($input_parameters = false) {
    if ($input_parameters!==false) {
      $keys = array_keys($input_parameters);
      if (!$keys[0]) {
        $vals = array_values($input_parameters);
        $input_parameters = array_combine(range(1, count($vals)), $vals);
      }
      $this->params = $input_parameters;
    }    
    curl_setopt ($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->params));
    $headers = array();
    $headers[] = 'X-req-id: '  .$this->db->requestId;
    $headers[] = 'X-req-uri: ' .$this->db->requestUri;
    $headers[] = 'X-ses-name: '.$this->db->sessionName;
    $headers[] = 'X-ses-ip: '  .$this->db->clientIp;
    $headers[] = 'X-req-user: '.$this->db->userId;
    $headers[] = 'X-aut-user: '.$this->db->username;
    $headers[] = 'X-aut-pass: '.$this->db->password;
    $headers[] = 'X-ses-stor: '.$this->db->sessionStorage;
    $this->db->sessionStorage = '';
    foreach ($this->attributes as $number=>$value) $headers[] = "X-pdo-stat-$number: $value";
    foreach ($this->serverAttrs as $number=>$value) $headers[] = "X-pdo-serv-$number: $value";
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    $start = microtime(true);
    $response = curl_exec($this->ch);
    $time = round((microtime(true) - $start)*1000);
    list($headers, $body) = explode("\r\n\r\n", $response, 2);
    $hits = preg_match_all('/(X-[a-z]+-[a-z]+): (.*)\r/', $headers, $matches);
    $queryId = false;
    for ($i=0;$i<$hits;$i++) {
      $key = $matches[1][$i];
      $val = $matches[2][$i];
      switch ($key) {
        case 'X-qry-id': $queryId = $val; break;
        case 'X-req-id': $this->db->requestId = $val; break;
      }
    }
    if ($queryId) $this->db->sessionStorage.="$time|$queryId&";
    $this->array = json_decode($body);
    $this->position = 0;
    $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    if ($this->array===null) {
      $this->array = array(0,'PHP_E',$body);
      $status = 500;
    }
    $jsonError = array_shift($this->array);
    if ($jsonError) $this->array = $this->decodeStrings($this->array);
    if ($status==200) {
      $this->errorCode = '00000';
      $this->errorInfo = array();
      $this->rowCount = array_shift($this->array);
      $this->db->setLastInsertId(array_shift($this->array));
      $this->meta = array_shift($this->array);
      $result = true;
    } elseif ($status==400) {
      $this->errorCode = array_shift($this->array);
      $this->errorInfo = array_shift($this->array);
      $result = false;
    } else {
      $errno = array_shift($this->array);
      $errstr = array_shift($this->array);
      $errfile = array_shift($this->array);
      $errline = array_shift($this->array);
      die("$errstr, $errno, 0, $errfile, $errline");
      throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
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
    $data = $this->current();
    if ($data===false) return false;
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
    return $result;
  }
  
  public function fetchColumn($column = 0) {
    $data = $this->current();
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
  
  public function debugDumpParams() {
    $len = strlen($this->queryString);
    echo "SQL: [$len] $this->queryString\n";
    $len = count($this->params);
    echo "Params:  $len\n";
    foreach ($this->params as $name=>$value) {
      $len = strlen($name);
      $num = $name[0]==':'?-1:$name-1;
      if ($num==-1) echo "Key: Name: [$len] $name\n";
      else echo "Key: Position #$num:\n";
      echo "paramno=$num\n";
      if ($num==-1) echo "name=[$len] \"$name\"\n";
      else echo "name=[0] \"\"\n";
      echo "is_param=1\n";
      $type = is_int($value)?1:2;
      echo "param_type=$type\n";
    }    
  }
}

class VirtualDbServer /* extends PDO */
{
  /* PDO {
   done public __construct ( string $dsn [, string $username [, string $password [, array $driver_options ]]] )
   done public bool beginTransaction ( void )
   done public bool commit ( void )
   done public mixed errorCode ( void )
   done public array errorInfo ( void )
   done public int exec ( string $statement )
   done public mixed getAttribute ( int $attribute )
   done public static array getAvailableDrivers ( void )
   done public bool inTransaction ( void )
   done public string lastInsertId ([ string $name = NULL ] )
   done public PDOStatement prepare ( string $statement [, array $driver_options = array() ] )
   done public PDOStatement query ( string $statement )
   args public string quote ( string $string [, int $parameter_type = PDO::PARAM_STR ] )
   done public bool rollBack ( void )
   done public bool setAttribute ( int $attribute , mixed $value )
   }*/
  
  private $ch;
  private $url;
  private $lastStatement;
  private $lastInsertId;
  private $attributes;
  private $inTransaction;
  
  public $username;
  public $password;
  public $database;
 
  public $clientIp;
  public $sessionName;
  public $requestId;
  public $userId;
  
  public $sessionStorage;
  
  public function __construct($dsn, $username = false, $password = false, $attributes = array()) {
    $this->ch = curl_init();
    curl_setopt ($this->ch, CURLOPT_POST, true);
    curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt ($this->ch, CURLOPT_COOKIEJAR, '/dev/null');
    curl_setopt ($this->ch, CURLOPT_HEADER, true);
    curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, true);
    if (!is_array($dsn)) {
      list($driver,$string) = explode(':',$dsn,2);
      $dsn = array();
      $params = explode(';',$string);
      foreach ($params as $param) {
        list($key,$value) = explode('=',$param,2);
        $dsn[$key] = $value;
      }
      $dsn['driver'] = $driver;
    }
    $this->username = $username;
    $this->password = $password;
    $this->database = $dsn['dbname'];
    $this->url = str_replace('__DATABASE__', $dsn['dbname'], $dsn['host']);
    $this->lastStatement = false;
    $this->lastInsertId = false;
    $this->attributes = array();
    foreach($attributes as $index => $value) {
      $this->setAttribute($index,$value);
    }
    $this->clientIp = isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
    $this->sessionName = session_id();
    $this->requestId = false;
    $this->requestUri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
    $this->userId = '';
    if (!isset($_SESSION['VirtualDbServer'])) {
      $_SESSION['VirtualDbServer'] = '';
    }
    $this->sessionStorage =& $_SESSION['VirtualDbServer'];
    if ($this->requestUri) {
      register_shutdown_function(array($this,'shutdown'));
    }
  }
  
  public function shutdown() {
    $time = false;
    if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
      $time = (int)((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'])*1000);
    }
    $html = false;
    $headers = headers_list();
    foreach ($headers as $header) {
      if (preg_match('/Content-Type:(.*)/',$header,$matches)) {
        $html = preg_match('/html/i',$matches[1]);
        break;
      }
    }
    if ($this->requestId!==false && $html) {
      $timeUrl = preg_replace('/db\.php.*/','time.php',$this->url);
      $now = time();
      $javascript = <<<END_OF_SCRIPT
var duration = performance.timing.responseEnd - performance.timing.requestStart;
var url = "$timeUrl?request=" + duration + "|$time|$this->requestId&session=$this->sessionStorage";
END_OF_SCRIPT;
      echo "<script type=\"text/javascript\">\n$javascript\n";
      echo "if (performance.navigation.type!=2) document.write('<script src=\"'+url+'\" defer><'+'/script>');\n";
      echo "</script>";
    }
  }  
  
  public function beginTransaction() {
    $this->inTransaction = $this->execute('BEGIN');
    return $this->inTransaction;
  }
  
  public function commit() {
    $this->inTransaction = !$this->execute('COMMIT');
    return !$this->inTransaction;
  }
  
  public function rollBack() {
    $this->inTransaction = !$this->execute('ROLLBACK');
    return !$this->inTransaction;
  }
  
  public function inTransaction() {
    return $this->inTransaction;
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
  
  public function quote($string,$type=PDO::PARAM_STR) {
    // is this safe when server and client character-set are utf8?
    $startQuote = '\'';
    $endQuote = '\'';
    if (is_null($string)) {
      return null;
    }
    if (is_array($string)) {
      return array_map(__METHOD__, $string);
    }
    if ($type == PDO::PARAM_BOOL) {
      $string = $string?1:0;
    }
    if ($type == PDO::PARAM_INT) {
      $string += 0;
    }
    if ($type == PDO::PARAM_STR) {
      $search = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
      $replace = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
      $string = $startQuote.str_replace($search,$replace,$string).$endQuote;
    }
    return $string;
  }
  
  public function prepare($statement,$attributes = array()) {
    $this->attributes = array_merge($this->attributes,$attributes);
    curl_setopt ($this->ch, CURLOPT_URL, str_replace('__QUERY__', urlencode($statement), $this->url));
    $this->lastStatement = new VirtualDbStatement($statement, $this, $this->ch, $attributes, $this->attributes);
    return $this->lastStatement;
  }
  
  private function execute($statement) {
    $this->lastStatement = $this->prepare($statement);
    return $this->lastStatement->execute();
  }
  
  public function exec($statement) {
    $this->execute($statement);
    return $this->lastStatement->rowCount();
  }
  
  public function query($statement) {
    $this->execute($statement);
    return $this->lastStatement;
  }
  
  public function getAttribute($index) {
    if ($index == PDO::MYSQL_ATTR_INIT_COMMAND) {
      $result = 'SET NAMES utf8';
    } elseif ($index == PDO::ATTR_ERRMODE) {
      $result = PDO::ERRMODE_EXCEPTION;
    } else {
      $result = $this->attributes[$index];
    }
    return $result;
  }
  
  public function setAttribute($index,$value) {
    if ($index == PDO::MYSQL_ATTR_INIT_COMMAND) {
      // ignore or throw
    } elseif ($index == PDO::ATTR_ERRMODE) {
      // ignore or throw
    } else {
      $this->attributes[$index] = $value;
    }
  }
  
}