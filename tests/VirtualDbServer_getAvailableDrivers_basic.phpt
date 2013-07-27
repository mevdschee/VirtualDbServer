--TEST--
getAvailableDrivers() function - basic test for VirtualDbServer::getAvailableDrivers()
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
    echo (in_array('mysql',VirtualDbServer::getAvailableDrivers())?'t':'f')."\n";
}
--EXPECT--
===PDO===
t
===VirtualDbServer===
t