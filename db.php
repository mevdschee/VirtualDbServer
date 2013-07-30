<?php
$f = fopen('db.log', 'a'); //debug access log

function exception_handler($e) {
  global $f;
  fwrite($f, "=== ERROR:\n".json_encode($e)."\n");
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
  fwrite($f, "=== full output:\n$str\n");
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

$headers = apache_request_headers();
$serverAttrs = array();
$attributes = array();
$clientIp = false;
$sessionId = false;
$requestUri = false;
$auth = array();
foreach ($headers as $name=>$value) {
  if (preg_match('/^X-(.*)-(.*)/',$name,$matches)) {
     switch($matches[1]) {
       case 'Server': $serverAttrs[$matches[2]]=$value; break;
       case 'Statement': $attributes[$matches[2]]=$value; break;
       case 'Client': if ($matches[2]=='Ip') $clientIp = $value; break;
       case 'Session': if ($matches[2]=='Id') $sessionId = $value; break;
       case 'Request': if ($matches[2]=='Uri') $requestUri = $value; break;
       case 'Auth': $auth[strtolower($matches[2])] = $value; break;
     }
  }
}
$database = $_GET['database'];
$query = $_GET['query'];
fwrite($f, "=== query:\n$clientIp\n$sessionId\n$requestUri\n$database\n$query\n");
$serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
$serverAttrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$dsn = "mysql:dbname=$database;host=localhost";
$db = new PDO($dsn,$auth['username'],$auth['password'],$serverAttrs);
$stmt = $db->prepare($query,$attributes);
fwrite($f, "=== post:\n".var_export($_POST,true)."\n");
foreach ($_POST as $parameter => $value)
{ if ($parameter[0]==':') $stmt->bindValue($parameter, $value);
  else $stmt->bindValue($parameter+1, $value);
}
$stmt->execute();
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
fwrite($f, "=== full output:\n$str\n");
fclose($f);
echo $str;
