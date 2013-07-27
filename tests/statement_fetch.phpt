--TEST--
VirtualDbStatement::fetch()                     @
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
    $modes = array(
        PDO::FETCH_ASSOC, //returns an array indexed by column name as returned in your result set
        PDO::FETCH_BOTH,  //returns an array indexed by both column name and 0-indexed column number as returned in your result set
//        PDO::FETCH_BOUND, //returns TRUE and assigns the values of the columns in your result set to the PHP variables to which they were bound with the PDOStatement::bindColumn() method
//        PDO::FETCH_CLASS, //returns a new instance of the requested class, mapping the columns of the result set to named properties in the class. 
//        PDO::FETCH_INTO,  //updates an existing instance of the requested class, mapping the columns of the result set to named properties in the class
        PDO::FETCH_LAZY,  // combines PDO::FETCH_BOTH and PDO::FETCH_OBJ, creating the object variable names as they are accessed
        PDO::FETCH_NUM,   //returns an array indexed by column number as returned in your result set, starting at column 0
        PDO::FETCH_OBJ,   //returns an anonymous object with property names that correspond to the column names returned in your result set
    );    
    foreach ($modes as $mode) {
        $stmt = $dbh->query($sql);
        while ($result = $stmt->fetch($mode)) {
            echo $mode.': '.json_encode($result)."\n";
        }
    }    
}

--EXPECT--
===PDO===
2: {"key":"one","val":"1"}
2: {"key":"two","val":"2"}
4: {"key":"one","0":"one","val":"1","1":"1"}
4: {"key":"two","0":"two","val":"2","1":"2"}
1: {"queryString":"SELECT * FROM test","key":"one","val":"1"}
1: {"queryString":"SELECT * FROM test","key":"two","val":"2"}
3: ["one","1"]
3: ["two","2"]
5: {"key":"one","val":"1"}
5: {"key":"two","val":"2"}
===VirtualDbServer===
2: {"key":"one","val":"1"}
2: {"key":"two","val":"2"}
4: {"key":"one","0":"one","val":"1","1":"1"}
4: {"key":"two","0":"two","val":"2","1":"2"}
1: {"queryString":"SELECT * FROM test","key":"one","val":"1"}
1: {"queryString":"SELECT * FROM test","key":"two","val":"2"}
3: ["one","1"]
3: ["two","2"]
5: {"key":"one","val":"1"}
5: {"key":"two","val":"2"}