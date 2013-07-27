--TEST--
VirtualDbServer::__construct()                  @
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
$dbhs = array(
    array('PDO',$pdo_dsn,$pdo_username,$pdo_password),
    array('VirtualDbServer',$vdb_dsn,$vdb_username,$vdb_password)
);
foreach ($dbhs as $dbh) { 
    echo "===".$dbh[0]."===\n";
    try {
        $db = new $dbh[0]($dbh[1],$dbh[2],$dbh[3]);
        echo "PDO connection object created\n";
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}
--EXPECT--
===PDO===
PDO connection object created
===VirtualDbServer===
PDO connection object created