--TEST--
getAvailableDrivers() function - basic test for VirtualDbServer::getAvailableDrivers()
--FILE--
<?php
require 'VirtualDbServer.php';
echo in_array('mysql',VirtualDbServer::getAvailableDrivers())?'t':'f';
--EXPECT--
t