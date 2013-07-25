<?php
$query = $_GET['query'];
$headers = apache_request_headers();
foreach ($headers as $name=>$value) {
  if (preg_match('/^X-Server-(.*)/',$name,$matches)) $serverAttrs[$matches[1]]=$value;
}
$db = new PDO('mysql:host=localhost;dbname=winksys-adu;charset=utf8', 'winksys-adu', 'winksys-adu', $serverAttrs);
$attributes = array();
foreach ($headers as $name=>$value) {
  if (preg_match('/^X-Statement-(.*)/',$name,$matches)) $attributes[$matches[1]]=$value;
}
$stmt = $db->prepare($query,$attributes);
foreach ($_POST as $parameter => $value)
{ if ($parameter[0]==':') $stmt->bindValue($parameter, $value);
  else $stmt->bindValue($parameter+1, $value);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_NUM);
echo json_encode($stmt->rowCount())."\n";
echo json_encode($db->lastInsertId())."\n";
$meta = array();
for ($i=0;$i<$stmt->columnCount();$i++) $meta[$i] = $stmt->getColumnMeta($i);
echo json_encode($meta)."\n";
foreach ($results as $result) echo json_encode($result)."\n";
