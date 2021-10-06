<?php
require_once __DIR__ . '/AlertBotMessage.php';
require_once __DIR__ . '/AlertBot.php';
$bot_message = new AlertBotMessage([], 'Завтра истекает срок:'."\n");

$spreadsheets = file(__DIR__ . '/spreadsheets.txt');

$bot_message->setSpreadsheets($spreadsheets);
$msg = $bot_message->getBotMessage(time(), 1);

$bot = new AlertBot(false);
$bot->sendMessageToAllAuthorizedUsers($msg);