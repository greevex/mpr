<?php
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/mpr.php';

$mpr = new mpr();
$function = isset($argv[1]) ? $argv[1] : 'help';
$params = isset($argv[2]) ? $argv[2] : '';
if(!method_exists($mpr, $function)) {
    $function = 'help';
}
$mpr->{$function}($params);

__halt_compiler();