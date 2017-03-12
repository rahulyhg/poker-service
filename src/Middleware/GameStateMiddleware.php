<?php

namespace PokerEngine\Middleware;

use Psr\Container\ContainerInterface;

class GameStateMiddleware
{
    private $gameService;

    public function __construct(ContainerInterface $container)
    {
        $this->gameService = $container->gameService;
    }

    public function __invoke($request, $response, $next)
    {
        $response = $next($request, $response);

        file_put_contents('../state.ser', serialize($this->gameService));

        return $response;
    }
}