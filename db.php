<?php
try {
  $f = fopen('db.log', 'a'); //debug access log
  ob_start();
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
  echo json_encode($stmt->rowCount())."\n";
  echo json_encode($db->lastInsertId())."\n";
  $meta = array();
  for ($i=0;$i<$columnCount;$i++) $meta[$i] = $stmt->getColumnMeta($i);
  echo json_encode($meta)."\n";
  if ($columnCount) {
    while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
//       if (some binary enable flag is set) {
//         for ($i=0;$i<$stmt->columnCount();$i++) {
//           if ($meta[$i]['native_type']=='BLOB') {
//             if ($data[$i]!==null) $data[$i] = base64_encode($data[$i]);
//           }
//           if (in_array($meta[$i]['native_type'],array('STRING','VAR_STRING'))) {
//             if ($data[$i]!==null) $data[$i] = utf8_encode($data[$i]);
//           }
//         }
//       }
      echo json_encode($data)."\n";
      if ($error = json_last_error()) {
        fwrite($f, "=== JSON error: $error\n");
        // throw? 
        //     0 = JSON_ERROR_NONE
        //     1 = JSON_ERROR_DEPTH
        //     2 = JSON_ERROR_STATE_MISMATCH
        //     3 = JSON_ERROR_CTRL_CHAR
        //     4 = JSON_ERROR_SYNTAX
        //     5 = JSON_ERROR_UTF8
      }
    }
  }
  $output = ob_get_contents();
  fwrite($f, "=== full output:\n".$output);
  ob_end_clean();
  fclose($f);
  echo $output;
} catch (Exception $e) {
  ob_clean();
  if ($e instanceof PDOException) {
    header('HTTP/1.1 400 Bad Request',true,400);
    echo json_encode($e->getCode())."\n";
    echo json_encode($e->errorInfo)."\n";
  } else {    
    header('HTTP/1.1 500 Internal Server Error',true,500);
    echo json_encode($e->getCode())."\n";
    echo json_encode($e->getMessage())."\n";
    // log to error log
  }
  $output = ob_get_contents();
  fwrite($f, "=== full output:\n".$output);
  ob_end_clean();
  fclose($f);
  echo $output;
}