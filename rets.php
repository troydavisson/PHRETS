<?php
require_once("vendor/autoload.php");
$config = new \PHRETS\Configuration;

$config->setLoginUrl('http://reb.retsiq.com:80/contact/rets/Login')
        ->setUsername('RETSREDMAN')
        ->setPassword('PASSWORD68')
        ->setRetsVersion('1.7');



$config->setUserAgent('RETSRedman/1.2');
$config->setUserAgentPassword('*!vowidx**');
$config->setHttpAuthenticationMethod('basic');

$rets = new \PHRETS\Session($config);

$connect = $rets->Login();
$system = $rets->GetSystemMetadata();

$resources = $system->getResources();
//print_r($resources);
$classes = $resources->first()->getClasses();
//print_r($classes);

$results = $rets->Search('Property', 'CM_1', '*', ['Limit' => 1999999]);
foreach ($results->toArray() as $r) {
    print_r($r);
echo "count:".count($r)."\n";
die;
echo "\n";
}

