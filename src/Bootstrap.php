<?php

namespace PokerEngine;

use PokerEngine\Middleware\GameStateMiddleware;
use Slim\App;
use Slim\Container;

class Bootstrap
{
    private $app;

    public function __construct()
    {
        $this->app = new App($this->buildContainer());

        $this->loadMiddlewares();
        $this->loadRoutes();
    }

    private function buildContainer()
    {
        $container = new Container();

        $container['settings']['displayErrorDetails'] = true;

        $container['gameService'] = function () {
            return unserialize(file_get_contents('../state.ser'));
        };

        return $container;
    }

    private function loadMiddlewares()
    {
        $this->app->add(new GameStateMiddleware($this->app->getContainer()));
    }

    private function loadRoutes()
    {
        $app = $this->app;

        require __DIR__ . '/../config/routes.php';
    }

    public function run()
    {
        $this->app->run();
    }
}