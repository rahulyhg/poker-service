<?php

use PokerEngine\Controller\GameController;

$app->post('/game/create', GameController::class . ':create');

$app->get('/game/call', GameController::class . ':call');
$app->get('/game/fold', GameController::class . ':fold');

$app->post('/game/raise', GameController::class . ':raise');
