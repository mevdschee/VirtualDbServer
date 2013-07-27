--TEST--
VirtualDbStatement::fetch(PDO::FETCH_CLASS)     @
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
class TestElement {
    public $key;
    public $val;
    
    public function capitalizeKey() {
     return ucwords($this->key);
    }
}
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
    $stmt = $dbh->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_CLASS,'TestElement');
    foreach($result as $testElement) {
      echo $testElement->capitalizeKey()."\n";
    }
    $stmt = $dbh->query($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS,'TestElement');
    while ($testElement = $stmt->fetch()) {
      echo $testElement->capitalizeKey()."\n";
    }
    $sql = "SELECT 'TestElement',`key`,`val` FROM test";
    $stmt = $dbh->query($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE);
    while ($testElement = $stmt->fetch()) {
      echo $testElement->capitalizeKey()."\n";
    }
}

--EXPECT--
===PDO===
One
Two
One
Two
One
Two
===VirtualDbServer===
One
Two
One
Two
One
Two