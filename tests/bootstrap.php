<?php

$directory = dirname(realpath(__FILE__));
define('FIXTUREDIR', $directory .'/Integration/Fixtures/VCR');

require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/Integration/BaseIntegration.php");
