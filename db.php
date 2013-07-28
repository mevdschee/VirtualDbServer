<?php
require __DIR__.'/config.php';
$f = fopen('db.log', 'a');
ob_start();
$query = $_GET['query'];
fwrite($f, "=== query:\n".$query."\n");
$headers = apache_request_headers();
$serverAttrs = array();
foreach ($headers as $name=>$value) {
  if (preg_match('/^X-Server-(.*)/',$name,$matches)) $serverAttrs[$matches[1]]=$value;
}
$serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
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
if($stmt->errorCode() && $stmt->errorCode() != '00000') {
  header('HTTP/1.1 400 Bad Request',true,400);
  echo json_encode($stmt->errorCode())."\n";
  echo json_encode($stmt->errorInfo())."\n";
} else {
  $results = $stmt->fetchAll(PDO::FETCH_NUM);
  echo json_encode($stmt->rowCount())."\n";
  echo json_encode($db->lastInsertId())."\n";
  $meta = array();
  for ($i=0;$i<$stmt->columnCount();$i++) $meta[$i] = $stmt->getColumnMeta($i);
  echo json_encode($meta)."\n";
  foreach ($results as $result) {
    for ($i=0;$i<$stmt->columnCount();$i++) {
      if ($meta[$i]['native_type']=='BLOB') {
        $result[$i] = base64_encode($result[$i]); 
      }
    }
    echo json_encode($result)."\n";
    if ($error = json_last_error()) fwrite($f, "=== JSON error: $error\n"); 
    //     0 = JSON_ERROR_NONE
    //     1 = JSON_ERROR_DEPTH
    //     2 = JSON_ERROR_STATE_MISMATCH
    //     3 = JSON_ERROR_CTRL_CHAR
    //     4 = JSON_ERROR_SYNTAX
    //     5 = JSON_ERROR_UTF8
  }
}
$output = ob_get_contents();
fwrite($f, "=== full output:\n".$output);
ob_end_clean();
fclose($f);
echo $output;