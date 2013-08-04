<?php
ini_set('default_socket_timeout', -1);
$r = new Redis();
$r->connect('localhost');
$dsn = "mysql:dbname=virtualdbserver;host=localhost";
$serverAttrs = array();
$serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
$serverAttrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$dbh = new PDO($dsn,'virtualdbserver','virtualdbserver',$serverAttrs);
$callStatement = $dbh->prepare("INSERT INTO `calls` (`id`, `client_ip`, `application_ip`, `session_id`, `request_uri`, `request_id`, `database`, `created_at`, `created_at_usec`, `call_time`, `execution_time`, `query_time`, `query`, `json_error`, `response_size`)
VALUES (?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, NULL, ?, ?, ?, ?, ?);");
$timingStatement = $dbh->prepare("UPDATE `calls` SET `call_time` = ? WHERE `id` = ?;");
while (true) {
  list($type,$data) = $r->blPop(array('calls','timings'), 0);
  $data = explode('|',$data);
  if ($type=='calls') {
    $callStatement->execute($data);
  } elseif ($type=='timings') {
    $timingStatement->execute($data);
  }
}
