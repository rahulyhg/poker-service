<?php

namespace PokerEngine\Service;

use PokerEngine\Entity\Player;

class GameService
{
    const STATE_BEFORE_FLOP = 0;
    const STATE_FLOP = 1;
    const STATE_TURN = 2;
    const STATE_RIVER = 3;

    private $deckService;
    private $bestHandService;
    /** @var Player[] */
    private $players;
    private $dealerIndex;
    private $smallBlind = 15;
    private $bigBlind = 30;
    private $currentPlayerIndex;
    private $pot;
    private $maxPlayersPot;
    private $currentState;
    private $cardsOnBoard;

    public function __construct(DeckService $deckService, BestHandService $bestHandService)
    {
        $this->deckService = $deckService;
        $this->bestHandService = $bestHandService;
    }

    public function start(array $playerIds)
    {
        $this->players = [];
        $this->registerPlayers($playerIds);
        $this->dealerIndex = 0;

        return $this->initializeStart();
    }

    private function initializeStart()
    {
        foreach ($this->players as $player) {
            $player->clearCoinsInCurrentPot();
            $player->clearHand();
            $player->setIsFolded(false);
            $player->setWasInTheRound(false);
        }

        $this->currentPlayerIndex = $this->calculatePlayerIndex(3);

        $this->deckService->shuffleDeck();
        $this->dealing();

        $this->pot = $this->smallBlind + $this->bigBlind;
        $this->maxPlayersPot = $this->bigBlind;
        $this->getSmallBlindPlayer()->decreaseCoins($this->smallBlind);
        $this->getBigBlindPlayer()->decreaseCoins($this->bigBlind);

        $this->currentState = self::STATE_BEFORE_FLOP;

        return [
            'players' => $this->getPlayers(),

            'smallBlind' => $this->getSmallBlind(),
            'bigBlind' => $this->getBigBlind(),

            'dealer' => $this->getDealer()->getId(),
            'smallBlindPlayer' => $this->getSmallBlindPlayer()->getId(),
            'bigBlindPlayer' => $this->getBigBlindPlayer()->getId(),

            'nextPlayer' => $this->getCurrentPlayer()->getId(),

            'pot' => $this->getPot(),
        ];
    }

    public function getPlayers()
    {
        $players = [];

        foreach ($this->players as $player) {
            $players[] = [
                'id' => $player->getId(),
                'coins' => $player->getCoins(),
                'hand' => $player->getHand(),
            ];
        }

        return $players;
    }

    private function registerPlayers($playerIds)
    {
        foreach ($playerIds as $playerId) {
            $this->players[] = new Player($playerId, 1000);
        }
    }

    private function dealing()
    {
        foreach ($this->players as &$player) {
            $player->dealCard($this->deckService->pop());
            $player->dealCard($this->deckService->pop());
        }
    }

    public function getDealer()
    {
        return $this->players[$this->dealerIndex];
    }

    public function getSmallBlindPlayer()
    {
        return $this->calculatePlayer(1);
    }

    public function getBigBlindPlayer()
    {
        return $this->calculatePlayer(2);
    }

    public function getCurrentPlayer()
    {
        return $this->players[$this->currentPlayerIndex];
    }

    private function calculatePlayer($nextChairs)
    {
        return $this->players[$this->calculatePlayerIndex($nextChairs)];
    }

    private function calculatePlayerIndex($nextChairs)
    {
        return ($this->dealerIndex + $nextChairs) % count($this->players);
    }

    public function getSmallBlind()
    {
        return $this->smallBlind;
    }

    public function getBigBlind()
    {
        return $this->bigBlind;
    }

    public function getPot()
    {
        return $this->pot;
    }

    public function call()
    {
        $raise = $this->maxPlayersPot - $this->getCurrentPlayer()->getCoinsInCurrentPot();

        $this->getCurrentPlayer()->decreaseCoins($raise);
        $this->pot += $raise;

        $response['currentPlayer'] = $this->getCurrentPlayer()->getId();
        $response['coinsInCurrentPot'] = $this->getCurrentPlayer()->getCoinsInCurrentPot();
        $response['raise'] = $raise;

        return $response + $this->nextPlayer();
    }

    public function fold()
    {
        $this->getCurrentPlayer()->setIsFolded(true);

        $response['currentPlayer'] = $this->getCurrentPlayer()->getId();
        $response['action'] = 'fold';

        $winner = [];
        foreach ($this->players as $player) {
            if ($player->isFolded()) {
                continue;
            }

            $winner[] = $player;
        }

        if (count($winner) == 1) {
            $this->dealerIndex = ($this->dealerIndex + 1) % count($this->players);

            $winner[0]->increaseCoins($this->pot);

            return $response + ['winner' => [$winner[0]->getId()]] + $this->initializeStart();
        }

        return $response + $this->nextPlayer();
    }

    public function raise($coins)
    {
        $this->getCurrentPlayer()->decreaseCoins($coins);
        $this->pot += $coins;
        $this->maxPlayersPot = $coins < $this->maxPlayersPot ? $this->maxPlayersPot : $coins;

        $response['currentPlayer'] = $this->getCurrentPlayer()->getId();
        $response['coinsInCurrentPot'] = $this->getCurrentPlayer()->getCoinsInCurrentPot();
        $response['raise'] = $coins;

        return $response + $this->nextPlayer();
    }

    private function nextPlayer()
    {
        $this->getCurrentPlayer()->setWasInTheRound(true);

        $currentPlayerIndex = $this->getNextPlayerIndex();

        if (
            $this->players[$currentPlayerIndex]->wasInTheRound()
            && $this->players[$currentPlayerIndex]->getCoinsInCurrentPot() == $this->getCurrentPlayer()->getCoinsInCurrentPot()
        ) {
            return $this->endOfRound();
        }

        $this->currentPlayerIndex = $currentPlayerIndex;

        return ['nextPlayer' => $this->getCurrentPlayer()->getId()];
    }

    private function getNextPlayerIndex()
    {
        $currentPlayerIndex = ($this->currentPlayerIndex + 1) % count($this->players);
        while ($this->players[$currentPlayerIndex]->isFolded()) {
            $currentPlayerIndex = ($currentPlayerIndex + 1) % count($this->players);
        }

        return $currentPlayerIndex;
    }

    private function endOfRound()
    {
        $this->maxPlayersPot = 0;
        $this->currentPlayerIndex = $this->calculatePlayerIndex(2);
        $this->currentPlayerIndex = $this->getNextPlayerIndex();

        foreach ($this->players as $player) {
            $player->clearCoinsInCurrentPot();
            $player->setWasInTheRound(false);
        }

        switch ($this->currentState) {
            case self::STATE_BEFORE_FLOP:
                $this->cardsOnBoard = [
                    $this->deckService->pop(),
                    $this->deckService->pop(),
                    $this->deckService->pop(),
                ];

                $this->currentState = self::STATE_FLOP;
                $response['flop'] = $this->cardsOnBoard;
                break;
            case self::STATE_FLOP:
                $this->cardsOnBoard[] = $this->deckService->pop();

                $this->currentState = self::STATE_TURN;
                $response['turn'] = $this->cardsOnBoard[3];
                break;
            case self::STATE_TURN:
                $this->cardsOnBoard[] = $this->deckService->pop();

                $this->currentState = self::STATE_RIVER;
                $response['river'] = $this->cardsOnBoard[4];
                break;
            case self::STATE_RIVER:
                return $this->endOfGame();
        }

        return $response + [
                'pot' => $this->pot,
                'nextPlayer' => $this->getCurrentPlayer()->getId(),
            ];
    }

    private function endOfGame()
    {
        $winners = $this->bestHandService->getWinner($this->players, $this->cardsOnBoard);
        $winnersResponse = [];

        foreach ($winners as $winner) {
            $winner->increaseCoins(round($this->pot / count($winner)));
            $winnersResponse[] = ['id' => $winner->getId()];
        }

        $this->dealerIndex = ($this->dealerIndex + 1) % count($this->players);

        return ['winner' => $winnersResponse] + $this->initializeStart();
    }
}