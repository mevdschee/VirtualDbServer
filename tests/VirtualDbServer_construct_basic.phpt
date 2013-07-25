--TEST--
construct() function - basic test for VirtualDbServer::__construct()
--FILE--
<?php
require 'VirtualDbServer.php';
try {
  $db = new VirtualDbServer("mysql:dbname=winksys-adu;host=http://winksys-adu.local/db.php?query=", "winksys-adu", "winksys-adu" );
  echo "PDO connection object created";
} catch(PDOException $e) {
  echo $e->getMessage();
}
--EXPECT--
PDO connection object created