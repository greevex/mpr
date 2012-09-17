<?php
namespace mpr\server;

require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/generator.php';

$mpr = new generator();
$mpr->reindex();

__halt_compiler();