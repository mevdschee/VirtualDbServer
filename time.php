<?php
$r = new Redis();
$r->connect('localhost');

function debug($str) {
  $f = fopen('db.log', 'a'); //debug access log
  fwrite($f, $str."\n");
  fclose($f);
}
$str = $_GET['session'];
if ($str) $r->lPush('timings', $str);
$str = $_GET['request'];
if ($str) $r->lPush('times', $str);