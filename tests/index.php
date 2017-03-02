<?php
use PhpSimpleCas\PhpCas;

require '../vendor/autoload.php';

$phpCas = new PhpCas('https://cas.xjtu.edu.cn/');

if (isset($_GET['logout'])) {
    $phpCas->logout();
}

$user = $phpCas->getUserOrRedirect();
// $user = $phpCas->getUserOrRedirect(null, 'ticket', 5);
$ticket = $phpCas->getTicket();
var_dump($user);
var_dump($ticket);

