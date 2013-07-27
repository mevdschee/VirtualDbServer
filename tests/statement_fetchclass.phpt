--TEST--
VirtualDbStatement::fetch(PDO::FETCH_CLASS)     @
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
class TestElement {
    public $key;
    public $val;
    public $len;
    
    public function __construct($len = 1) {
        $this->len = $len;
    }
    
    public function capitalizeKey() {
        return strtoupper(substr($this->key,0,$this->len)).substr($this->key,$this->len);
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
    $stmt->setFetchMode(PDO::FETCH_CLASS,'TestElement',array(2));
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
ONe
TWo
One
Two
===VirtualDbServer===
One
Two
ONe
TWo
One
Two