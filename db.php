<?php
function encodeStrings(&$str) {
  if (is_array($str)) return array_map(__METHOD__, $str);
  if (is_string($str)) return base64_encode($str);
  return $str;
}
try {
  $f = fopen('db.log', 'a'); //debug access log
  require __DIR__.'/config.php';
  $query = $_GET['query'];
  fwrite($f, "=== query:\n".$query."\n");
  $headers = apache_request_headers();
  $serverAttrs = array();
  foreach ($headers as $name=>$value) {
    if (preg_match('/^X-Server-(.*)/',$name,$matches)) $serverAttrs[$matches[1]]=$value;
  }
  $serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
  $serverAttrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
  $db = new PDO($pdo_dsn,$pdo_username,$pdo_password,$serverAttrs);
  $attributes = array();
  foreach ($headers as $name=>$value) {
    if (preg_match('/^X-Statement-(.*)/',$name,$matches)) $attributes[$matches[1]]=$value;
  }
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
} catch (Exception $e) {
  $object = array(0);
  if ($e instanceof PDOException) {
    header('HTTP/1.1 400 Bad Request',true,400);
    $object[] = $e->getCode();
    $object[] = $e->errorInfo;
  } else {    
    header('HTTP/1.1 500 Internal Server Error',true,500);
    $object[] = $e->getCode();
    $object[] = $e->getMessage();
    // log to error log
  }
  $str = json_encode($object);
  $object[0] = json_last_error();
  if ($object[0]) $str = json_encode(encodeStrings($object));
  fwrite($f, "=== full output:\n$str\n");
  fclose($f);
  echo $str;
}