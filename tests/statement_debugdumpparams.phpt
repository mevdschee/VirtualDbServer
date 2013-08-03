--TEST--
VirtualDbServer::debugDumpParams()              @
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
    $stmt = $dbh->exec("CREATE TABLE `test` (`key` varchar(255) NOT NULL,`val` int(11) NOT NULL) COLLATE 'utf8_general_ci';");
    $stmt = $dbh->prepare("INSERT INTO `test` (`key`, val) VALUES (:key, :val)");
    $key = 'one';
    $stmt->bindParam(':key', $key, PDO::PARAM_STR);
    $stmt->bindValue(':val', 1, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->debugDumpParams();
    $stmt = $dbh->prepare("INSERT INTO `test` (`key`, val) VALUES (?, ?)");
    $key = 'two';
    $stmt->bindParam(1, $key, PDO::PARAM_STR);
    $stmt->bindValue(2, 2, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->debugDumpParams();
}
--EXPECT--
===PDO===
SQL: [51] INSERT INTO `test` (`key`, val) VALUES (:key, :val)
Params:  2
Key: Name: [4] :key
paramno=-1
name=[4] ":key"
is_param=1
param_type=2
Key: Name: [4] :val
paramno=-1
name=[4] ":val"
is_param=1
param_type=1
SQL: [45] INSERT INTO `test` (`key`, val) VALUES (?, ?)
Params:  2
Key: Position #0:
paramno=0
name=[0] ""
is_param=1
param_type=2
Key: Position #1:
paramno=1
name=[0] ""
is_param=1
param_type=1
===VirtualDbServer===
SQL: [51] INSERT INTO `test` (`key`, val) VALUES (:key, :val)
Params:  2
Key: Name: [4] :key
paramno=-1
name=[4] ":key"
is_param=1
param_type=2
Key: Name: [4] :val
paramno=-1
name=[4] ":val"
is_param=1
param_type=1
SQL: [45] INSERT INTO `test` (`key`, val) VALUES (?, ?)
Params:  2
Key: Position #0:
paramno=0
name=[0] ""
is_param=1
param_type=2
Key: Position #1:
paramno=1
name=[0] ""
is_param=1
param_type=1