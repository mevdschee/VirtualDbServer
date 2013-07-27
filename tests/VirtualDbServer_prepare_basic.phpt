--TEST--
prepare() function - basic test for VirtualDbServer::prepare()
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
$dbhs = array(
    new PDO("mysql:dbname=winksys-adu;host=localhost", "winksys-adu", "winksys-adu" ),
    new VirtualDbServer("mysql:dbname=winksys-adu;host=http://winksys-adu.local/db.php?query=", "winksys-adu", "winksys-adu" ),
  );
foreach ($dbhs as $dbh) { 
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
    if ($stmt->execute(array('two'))) echo json_encode($stmt->fetchAll())."\n";
}
--EXPECT--
[{"key":"two","0":"two","val":"2","1":"2"}]
[{"key":"two","0":"two","val":"2","1":"2"}]