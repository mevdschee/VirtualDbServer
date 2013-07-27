--TEST--
construct() function - basic test for VirtualDbServer::__construct()
--FILE--
<?php
require __DIR__.'/../VirtualDbServer.php';
require __DIR__.'/config.php';
$dbhs = array(
    array("PDO", "mysql:dbname=winksys-adu;host=localhost", "winksys-adu", "winksys-adu" ),
    array("VirtualDbServer", "mysql:dbname=winksys-adu;host=http://winksys-adu.local/db.php?query=", "winksys-adu", "winksys-adu" ),
  );
foreach ($dbhs as $dbh) { 
    try {
      $db = new $dbh[0]($dbh[1],$dbh[2],$dbh[3]);
      echo "PDO connection object created\n";
    } catch(PDOException $e) {
      echo $e->getMessage();
    }
}
--EXPECT--
PDO connection object created
PDO connection object created