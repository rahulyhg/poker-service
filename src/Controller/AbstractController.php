<?php

namespace PokerEngine\Controller;

use PokerEngine\Service\GameService;
use Psr\Container\ContainerInterface;

class AbstractController
{
    /** @var GameService */
    protected $gameService;

    public function __construct(ContainerInterface $container) {
        $this->gameService = $container->gameService;
    }
}