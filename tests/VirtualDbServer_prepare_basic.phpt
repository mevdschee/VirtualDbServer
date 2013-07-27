--TEST--
prepare() function - basic test for VirtualDbServer::prepare()
--FILE--
<?php
require 'VirtualDbServer.php';
$dbh = new VirtualDbServer("mysql:dbname=winksys-adu;host=http://winksys-adu.local/db.php?query=", "winksys-adu", "winksys-adu" );
$stmt = $dbh->exec("DROP TABLE IF EXISTS `test`;");
$stmt = $dbh->exec("CREATE TABLE `test` (`key` varchar(255) NOT NULL,`val` varchar(255) NOT NULL) COLLATE 'utf8_general_ci';");
$stmt = $dbh->prepare("INSERT INTO `test` (`key`, val) VALUES (:key, :val)");
$stmt->bindParam(':key', $key);
$stmt->bindParam(':val', $val);
// insert one row
$key = 'one';
$val = 1;
$stmt->execute();
// insert another row with different values
$key = 'two';
$val = 2;
$stmt->execute();
$stmt = $dbh->prepare("SELECT * FROM `test` where `key` = ?");
if ($stmt->execute(array('two'))) echo json_encode($stmt->fetchAll());
--EXPECT--
[["two","2"]]