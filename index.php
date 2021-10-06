<?php
require_once __DIR__ . '/AlertBot.php';

$update = json_decode(file_get_contents("php://input"), TRUE);

$bot = new AlertBot($update);

if ($bot->isTypeOfBotCommand()) {
    $bot->checkCommands();
}