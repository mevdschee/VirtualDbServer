--TEST--
VirtualDbServer::exec()                         @
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
    $dbh->exec("CREATE TABLE `test` (`key` varchar(255) NOT NULL,`val` varchar(255) NOT NULL) COLLATE 'utf8_general_ci';");
    $count = $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('k','v')");
    echo json_encode($count)."\n";
    $count = $dbh->exec("INSERT INTO `test` (`key`, val) VAL ('k','v')");
    echo json_encode($count)."\n";
    echo json_encode($dbh->errorCode())."\n";
    echo json_encode($dbh->errorInfo())."\n";
}
--EXPECT--
===PDO===
1
false
"42000"
["42000",1064,"You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'VAL ('k','v')' at line 1"]
===VirtualDbServer===
1
false
"42000"
["42000",1064,"You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'VAL ('k','v')' at line 1"]