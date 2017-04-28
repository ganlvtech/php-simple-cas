<?php
use PhpSimpleCas\PhpCas;

require '../vendor/autoload.php';

$phpCas = new PhpCas('https://cas.xjtu.edu.cn/');

if (isset($_GET['logout'])) {
    $phpCas->logout();
}

if (isset($_GET['login'])) {
    $phpCas->forceAuthentication();
}

$auth = $phpCas->checkAuthentication();
if ($auth) {
    echo $phpCas->getUser();
} else {
    echo 'Guest mode';
}
