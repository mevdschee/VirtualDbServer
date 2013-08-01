--TEST--
VirtualDbServer::beginTransaction()             @
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
    $dbh->exec("DROP TABLE IF EXISTS `test`;");
    $dbh->exec("CREATE TABLE `test` (`key` varchar(255) NOT NULL,`val` int(11) NOT NULL) COLLATE 'utf8_general_ci';");
    $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('one',1)");
    $dbh->beginTransaction();
    $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('two',2)");
    $dbh->rollBack();
    $stmt = $dbh->query("SELECT * FROM test");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result)."\n";
    $dbh->beginTransaction();
    $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('two',2)");
    $dbh->commit();
    $stmt = $dbh->query("SELECT * FROM test");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result)."\n";
}
--EXPECT--
===PDO===
[{"key":"one","val":"1"}]
[{"key":"one","val":"1"},{"key":"two","val":"2"}]
===VirtualDbServer===
[{"key":"one","val":"1"}]
[{"key":"one","val":"1"},{"key":"two","val":"2"}]