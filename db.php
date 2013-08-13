<?php
$start = microtime(true);
session_start();

$r = new Redis();
$r->connect('localhost');

if (!isset($_SESSION['requestId'])) {
  $requestId = $r->incr('#requests');
  $_SESSION['requestId'] = $requestId;
  header('X-req-id: '.$requestId);
} else $requestId = $_SESSION['requestId'];

function debug($str) {
  $f = fopen('db.log', 'a'); //debug access log
  fwrite($f, $str."\n");
  fclose($f);
}

function exception_handler($e) {
  //debug("=== ERROR: ".json_encode($e));
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
  //debug("=== full output: $str");
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

$headers = apache_request_headers();
$serverAttrs = array();
$attributes = array();
$requestUri = false;
$username = false;
$sessionName = false;
$clientIp = false;
$auth = array();
$timings = false;
foreach ($headers as $name=>$value) {
  if (preg_match('/^(X-[a-z]+-[a-z]+)(-([0-9]+))?/',$name,$matches)) {
     switch($matches[1]) {
       case 'X-pdo-serv': $serverAttrs[$matches[3]]=$value; break;
       case 'X-pdo-stat': $attributes[$matches[3]]=$value; break;
       case 'X-req-uri' : $requestUri = $value; break;
       case 'X-req-user': $username = $value; break;
       case 'X-ses-name': $sessionName = $value; break;
       case 'X-ses-ip'  : $clientIp = $value; break;
       case 'X-aut-user': $auth['username'] = $value; break;
       case 'X-aut-pass': $auth['password'] = $value; break;
       case 'X-ses-stor': $timings = $value; break;
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
//debug("=== post: ".var_export($_POST,true)." ");
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
//debug("=== full output: $str ");
//debug("=== timings\n");
if ($timings) {
  debug('timings: '.$timings);
  $r->lPush('timings', $timings);
}
$id = $r->incr('#queries');
header('X-qry-id: '.$id);
$serverIp = $_SERVER['REMOTE_ADDR'];
$responseSize = strlen($str);
$mseconds = (int)(($start-(int)$start)*1000);
$start = (int)$start;
$time = round((microtime(true) - $start)*1000);
$val = array($id,$clientIp,$serverIp,$sessionName,$username,$requestId,$requestUri,$database,$auth['username'],$start,$mseconds,$time,$timeQ,$query,$object[0],$responseSize);
debug('calls: '.json_encode($val));
$r->lPush('calls', json_encode($val));
echo $str;