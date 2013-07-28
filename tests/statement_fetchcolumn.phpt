--TEST--
VirtualDbStatement::fetchColumn()               @
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
    $stmt = $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('one', 1)");
    $stmt = $dbh->exec("INSERT INTO `test` (`key`, val) VALUES ('two', 2)");
    $stmt = $dbh->query("SELECT * FROM test");
    $key = $stmt->fetchColumn();
    echo "$key\n";
    $val = $stmt->fetchColumn(1);
    echo "$val\n";
}

--EXPECT--
===PDO===
one
2
===VirtualDbServer===
one
2