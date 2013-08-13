<?php
ini_set('default_socket_timeout', -1);
$r = new Redis();
$r->connect('localhost');
$dsn = "mysql:dbname=virtualdbserver;host=localhost";
$serverAttrs = array();
$serverAttrs[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
$serverAttrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$dbh = new PDO($dsn,'virtualdbserver','virtualdbserver',$serverAttrs);
$findServer = $dbh->prepare("SELECT id FROM `servers` WHERE `database` = ? and `server_ip` = ? and `username` = ?");
$addServer = $dbh->prepare("INSERT INTO `servers` (`database`, `server_ip`, `username`) VALUES (?, ?, ?);");
$findSession = $dbh->prepare("SELECT id FROM `sessions` WHERE `database` = ? and `name` = ? and `client_ip` = ? and `server_id` = ?");
$addSession = $dbh->prepare("INSERT INTO `sessions` (`database`, `name`, `client_ip`, `server_id`) VALUES (?, ?, ?, ?);");
$findRequest = $dbh->prepare("SELECT id FROM `requests` WHERE `id` = ?");
$addRequest = $dbh->prepare("INSERT INTO `requests` (`id`, `database`, `created_at`, `created_at_msec`, `session_id`, `username`, `request_uri`) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?, ?, ?);");
$updateRequestTimes = $dbh->prepare("UPDATE `requests` SET `call_time` = ?, `execution_time` = ? WHERE `id` = ?");
$findQuery = $dbh->prepare("SELECT id FROM `queries` WHERE `id` = ?");
$addQuery = $dbh->prepare("INSERT INTO `queries` (`id`, `database`, `created_at`, `created_at_msec`, `request_id`, `execution_time`, `query_time`, `query`, `json_error`, `response_size`) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?, ?, ?, ?, ?, ?);");
$updateQueryCallTime = $dbh->prepare("UPDATE `queries` SET `call_time` = ? WHERE `id` = ?");
while (true) {
  list($type,$data) = $r->brPop(array('calls','timings','times'), 0);
  if ($type=='calls') {
    $data = json_decode($data);
    list($queryId,$clientIp,$serverIp,$sessionName,$username,$requestId,$requestUri,$database,$dbuser,$start,$mseconds,$time,$timeQ,$query,$jsonError,$responseSize) = $data;
    $findServer->execute(array($database,$serverIp,$dbuser));
    if (!$findServer->rowCount()) {
      $addServer->execute(array($database,$serverIp,$dbuser));
      $serverId = $dbh->lastInsertId();
    } else {
      $serverId = $findServer->fetchColumn();
    }
    $findSession->execute(array($database,$sessionName,$clientIp,$serverId));
    if (!$findSession->rowCount()) {
      $addSession->execute(array($database,$sessionName,$clientIp,$serverId));
      $sessionId = $dbh->lastInsertId();
    } else {
      $sessionId = $findSession->fetchColumn();
    }
    $findRequest->execute(array($requestId));
    if (!$findRequest->rowCount()) {
      $addRequest->execute(array($requestId,$database,$start,$mseconds,$sessionId,$username,$requestUri));
    }
    $findQuery->execute(array($queryId));
    if (!$findQuery->rowCount()) {
      $addQuery->execute(array($queryId,$database,$start,$mseconds,$requestId,$time,$timeQ,$query,$jsonError,$responseSize));
    }
    //echo "$queryId,$clientIp,$serverIp,$sessionName,$username,$requestId,$requestUri,$database,$dbuser,$start,$mseconds,$time,$timeQ,$query,$jsonError,$responseSize\n";
  } elseif ($type=='timings') {
    //echo "$type,$data\n";
    $records = explode('&',rtrim($data,'&'));
    foreach ($records as $record) {
      $fields = explode('|',$record);
      $updateQueryCallTime->execute($fields);
    }
  } elseif ($type=='times') {
    $fields = explode('|',$data);
    $updateRequestTimes->execute($fields);
  }
}

