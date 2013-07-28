--TEST--
VirtualDbStatement::fetch(PDO::FETCH_BOUND)     @
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
    $sql = "SELECT * FROM test";
    $stmt = $dbh->prepare($sql);
    $stmt->bindColumn(1, $key);
    $stmt->bindColumn('val', $val);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_BOUND);
    while ($stmt->fetch()) {
        echo "$key:$val\n";
    }
}

--EXPECT--
===PDO===
one:1
two:2
===VirtualDbServer===
one:1
two:2