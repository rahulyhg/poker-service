<?php

namespace PokerEngine\Entity;

class Player
{
    private $id;
    private $coins;
    private $coinsInCurrentPot = 0;
    private $hand = [];
    private $isFolded = false;
    private $wasInTheRound = false;
    private $evaluatedHand;

    public function __construct($id, $coins)
    {
        $this->id = $id;
        $this->coins = $coins;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCoins()
    {
        return $this->coins;
    }

    public function getHand()
    {
        return $this->hand;
    }

    public function clearHand()
    {
        $this->hand = [];
    }

    public function dealCard($card)
    {
        $this->hand[] = $card;
    }

    public function decreaseCoins($coins)
    {
        $this->coins -= $coins;
        $this->coinsInCurrentPot += $coins;
    }

    public function increaseCoins($coins)
    {
        $this->coins += $coins;
    }

    public function getCoinsInCurrentPot()
    {
        return $this->coinsInCurrentPot;
    }

    public function clearCoinsInCurrentPot()
    {
        $this->coinsInCurrentPot = 0;
    }

    public function isFolded()
    {
        return $this->isFolded;
    }

    public function setIsFolded($isFolded)
    {
        $this->isFolded = $isFolded;
    }

    public function wasInTheRound()
    {
        return $this->wasInTheRound;
    }

    public function setWasInTheRound($wasInTheRound)
    {
        $this->wasInTheRound = $wasInTheRound;
    }

    public function getEvaluatedHand()
    {
        return $this->evaluatedHand;
    }

    public function setEvaluatedHand($evaluatedHand)
    {
        $this->evaluatedHand = $evaluatedHand;
    }
}