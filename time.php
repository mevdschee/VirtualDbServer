<?php
$r = new Redis();
$r->connect('localhost');

function debug($str) {
  $f = fopen('db.log', 'a'); //debug access log
  fwrite($f, $str."\n");
  fclose($f);
}
$str = $_GET['request'];
$r->lPush('times', $str);
$str = $_GET['session'];
$r->lPush('timings', $str);