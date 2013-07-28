VirtualDbServer
===============

VirtualDbServer is a (native) PHP implementation of the PDO MySQL driver over HTTP(S) using CURL and JSON.

Known Issues
----

 - UTF8 must be used; other charsets are not supported

To do
----

 - Transaction support
 - Multiple rowsets: http://www.php.net/manual/en/pdostatement.nextrowset.php
 - debugDumpParams: http://www.php.net/manual/en/pdostatement.debugdumpparams.php
 - Support server attributes: http://php.net/manual/en/pdo.setattribute.php
 - Support MySQL attributes: http://www.php.net/manual/en/ref.pdo-mysql.php
 - Include PDO constants
 - Add binary statement attribute for enabling BLOB and BINARY
 - Add error handling with hint to enable binary flag when JSON_ERROR occurs

Overview:

        PDO {
        done public __construct ( string $dsn [, string $username [, string $password [, array $driver_options ]]] )
             public bool beginTransaction ( void )
             public bool commit ( void )
        done public mixed errorCode ( void )
        done public array errorInfo ( void )
        done public int exec ( string $statement )
        done public mixed getAttribute ( int $attribute )
        done public static array getAvailableDrivers ( void )
             public bool inTransaction ( void )
        done public string lastInsertId ([ string $name = NULL ] )
        done public PDOStatement prepare ( string $statement [, array $driver_options = array() ] )
        done public PDOStatement query ( string $statement )
        args public string quote ( string $string [, int $parameter_type = PDO::PARAM_STR ] )
             public bool rollBack ( void )
        done public bool setAttribute ( int $attribute , mixed $value )
        }
        
        PDOStatement implements Traversable {
        done readonly string $queryString;
        args public bool bindColumn ( mixed $column , mixed &$param [, int $type [, int $maxlen [, mixed $driverdata ]]] )
        args public bool bindParam ( mixed $parameter , mixed &$variable [, int $data_type = PDO::PARAM_STR [, int $length [, mixed $driver_options ]]] )
        args public bool bindValue ( mixed $parameter , mixed $value [, int $data_type = PDO::PARAM_STR ] )
        done public bool closeCursor ( void )
        done public int columnCount ( void )
             public void debugDumpParams ( void )
        done public string errorCode ( void )
        done public array errorInfo ( void )
        done public bool execute ([ array $input_parameters ] )
        args public mixed fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        done public array fetchAll ([ int $fetch_style [, mixed $fetch_argument [, array $ctor_args = array() ]]] )
        done public string fetchColumn ([ int $column_number = 0 ] )
        done public mixed fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
        done public mixed getAttribute ( int $attribute )
        done public array getColumnMeta ( int $column )
             public bool nextRowset ( void )
        done public int rowCount ( void )
        done public bool setAttribute ( int $attribute , mixed $value )
        done public bool setFetchMode ( int $mode )
        }

