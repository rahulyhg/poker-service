<?php

namespace PokerEngine\Controller;

use Slim\Http\Request;
use Slim\Http\Response;

class GameController extends AbstractController
{
    public function create(Request $request, Response $response) {
        return $response->withJson($this->gameService->start($request->getParam('playerIds')));
    }

    public function call(Request $request, Response $response) {
        return $response->withJson($this->gameService->call());
    }

    public function fold(Request $request, Response $response) {
        return $response->withJson($this->gameService->fold());
    }

    public function raise(Request $request, Response $response) {
        return $response->withJson($this->gameService->raise($request->getParam('coins')));
    }
}