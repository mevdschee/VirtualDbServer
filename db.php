<?php
$start = microtime(true);
session_start();
$requestId = session_id();
$f = fopen('db.log', 'a'); //debug access log
$r = new Redis();
$r->connect('localhost');

function exception_handler($e) {
  global $f;
  //fwrite($f, "=== ERROR: ".json_encode($e)."\n");
  $object = array(0);
  $object[] = $e->getCode();
  if ($e instanceof PDOException && $e->errorInfo) {
    header('HTTP/1.1 400 Bad Request',true,400);
    $object[] = $e->errorInfo;
  } else {    
    header('HTTP/1.1 500 Internal Server Error',true,500);
    $object[] = $e->getMessage();
    $object[] = $e->getFile();
    $object[] = $e->getLine();
  }
  // log to error log
  $str = json_encode($object);
  //fwrite($f, "=== full output: $str\n");
  fclose($f);
  die($str);
}

set_exception_handler('exception_handler');
set_error_handler('errorHandler');
function errorHandler($errno, $errstr, $errfile, $errline) {
  throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

function encodeStrings(&$str) {
  if (is_array($str)) return array_map(__METHOD__, $str);
  if (is_string($str)) return base64_encode($str);
  return $str;
}

function guidv4() {
  $data = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$headers = apache_request_headers();
$serverAttrs = array();
$attributes = array();
$clientIp = false;
$sessionId = false;
$requestUri = false;
$auth = array();
$timings = array();
foreach ($headers as $name=>$value) {
  if (preg_match('/^X-([a-zA-Z]+)-([a-z0-9A-Z]+)/',$name,$matches)) {
     switch($matches[1]) {
       case 'Server': $serverAttrs[$matches[2]]=$value; break;
       case 'Statement': $attributes[$matches[2]]=$value; break;
       case 'Client': if ($matches[2]=='Ip') $clientIp = $value; break;
       case 'Session': if ($matches[2]=='Id') $sessionId = $value; break;
       case 'Request': if ($matches[2]=='Uri') $requestUri = $value; break;
       case 'Auth': $auth[strtolower($matches[2])] = $value; break;
       case 'Transfer': parse_str($value,$timings); break;
     }
  }
}
$database = $_GET['database'];
$query = $_GET['query'];
$serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
$serverAttrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$serverAttrs[PDO::ATTR_PERSISTENT] = true;
$dsn = "mysql:dbname=$database;port=3306;host=$requestId.6a.nl";
$db = new PDO($dsn,$auth['username'],$auth['password'],$serverAttrs);
$stmt = $db->prepare($query,$attributes);
//fwrite($f, "=== post: ".var_export($_POST,true)." ");
foreach ($_POST as $parameter => $value)
{ if ($parameter[0]==':') $stmt->bindValue($parameter, $value);
  else $stmt->bindValue($parameter+1, $value);
}
$startQ = microtime(true);
$stmt->execute();
$timeQ = round((microtime(true) - $startQ)*1000);
$columnCount = $stmt->columnCount();
$object = array(0);
$object[] = $stmt->rowCount();
$object[] = $db->lastInsertId();
$meta = array();
for ($i=0;$i<$columnCount;$i++) $meta[$i] = $stmt->getColumnMeta($i);
$object[] = $meta;
if ($columnCount) {
  while ($object[] = $stmt->fetch(PDO::FETCH_NUM));
}
$str = json_encode($object);
$object[0] = json_last_error();
if ($object[0]) $str = json_encode(encodeStrings($object));
//fwrite($f, "=== full output: $str ");
//fwrite($f, "=== timings\n");
foreach($timings as $id=>$t) {
  $val = "$t|$id";
  //fwrite($f, "$val\n");
  $r->rPush('timings', $val);
}
$id = guidv4();
$time = round((microtime(true) - $start)*1000);
$applicationIp = $_SERVER['REMOTE_ADDR'];
$useconds = ($start-(int)$start)*1000000;
$responseSize = strlen($str);
$val = "$id|$clientIp|$applicationIp|$sessionId|$requestUri|$requestId|$database|$start|$useconds|$time|$timeQ|$query|$object[0]|$responseSize";
//fwrite($f, "$val\n");
$r->rPush('calls', $val);
header('X-Request-Id: '.$id);
fclose($f);
echo $str;
