--TEST--
prepare() function - basic test for VirtualDbServer::prepare()
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
$dbhs = array(
  new PDO($pdo_dsn,$pdo_username,$pdo_password),
  new VirtualDbServer($vdb_dsn,$vdb_username,$vdb_password)
);
foreach ($dbhs as $dbh) { 
    echo "===".get_class($dbh)."===\n";
    $stmt = $dbh->exec("DROP TABLE IF EXISTS `test`;");
    $stmt = $dbh->exec("CREATE TABLE `test` (`key` varchar(255) NOT NULL,`val` varchar(255) NOT NULL) COLLATE 'utf8_general_ci';");
    $stmt = $dbh->prepare("INSERT INTO `test` (`key`, val) VALUES (:key, :val)");
    $stmt->bindParam(':key', $key);
    // insert one row
    $key = 'one';
    $val = 1;
    $stmt->bindValue(':val', $val);
    $stmt->execute();
    // insert another row with different values
    $key = 'two';
    $val = 2;
    $stmt->bindValue(':val', $val);
    $stmt->execute();
    $stmt = $dbh->prepare("SELECT * FROM `test` where `key` = ?");
    if ($stmt->execute(array('one'))) echo json_encode($stmt->fetchAll())."\n";
    if ($stmt->execute(array('two'))) echo json_encode($stmt->fetchAll())."\n";
}
--EXPECT--
===PDO===
[{"key":"one","0":"one","val":"1","1":"1"}]
[{"key":"two","0":"two","val":"2","1":"2"}]
===VirtualDbServer===
[{"key":"one","0":"one","val":"1","1":"1"}]
[{"key":"two","0":"two","val":"2","1":"2"}]