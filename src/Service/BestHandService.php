<?php

namespace PokerEngine\Service;

use PokerEngine\Entity\Player;

class BestHandService
{
    private $handEvaluator;
    /** @var Player[] */
    private $winner;

    /**
     * @param PokerHandEvaluatorService $handEvaluator
     */
    public function __construct($handEvaluator)
    {
        $this->handEvaluator = $handEvaluator;
    }

    /**
     * @param Player[] $players
     * @param array $cardsOnBoard
     *
     * @return Player[]
     */
    public function getWinner(array $players, array $cardsOnBoard) : array
    {
        $this->winner = [];

        $this->evaluateHands($players, $cardsOnBoard);
        $this->compareHands($players);

        return $this->winner;
    }

    private function evaluateHands(array $players, array $cardsOnBoard)
    {
        foreach ($players as $player)
        {
            $player->setEvaluatedHand(
                $this->handEvaluator->evaluate(array_merge($player->getHand(), $cardsOnBoard))
            );
        }
    }

    /**
     * @param Player[] $players
     */
    private function compareHands(array $players)
    {
        foreach ($players as $player)
        {
            if ($player->isFolded()) {
                continue;
            }

            $this->compareToWinner($player);
        }
    }

    private function compareToWinner(Player $player)
    {
        if (empty($this->winner))
        {
            $this->winner = [$player];

            return;
        }

        switch ($this->compareTwoHands($this->winner[0]->getEvaluatedHand(), $player->getEvaluatedHand()))
        {
            case -1:
                $this->winner = [$player];
                break;
            case 0:
                $this->winner[] = $player;
                break;
        }
    }

    private function compareTwoHands(array $firstHand, array $secondHand) : int
    {
        if ($firstHand['rank'] != $secondHand['rank'])
        {
            return $firstHand['rank'] <=> $secondHand['rank'];
        }

        switch ($firstHand['rank'])
        {
            case PokerHandEvaluatorService::HIGH_CARD:
            case PokerHandEvaluatorService::FLUSH:
                return $this->compareTwoArrays($firstHand['values'], $secondHand['values']);
            case PokerHandEvaluatorService::ONE_PAIR:
            case PokerHandEvaluatorService::THREE_OF_A_KIND:
                if ($firstHand['value'] != $secondHand['value'])
                {
                    return $firstHand['value'] <=> $secondHand['value'];
                }

                return $this->compareTwoArrays($firstHand['kickers'], $secondHand['kickers']);
            case PokerHandEvaluatorService::TWO_PAIR:
                if ($firstHand['high_value'] != $secondHand['high_value'])
                {
                    return $firstHand['high_value'] <=> $secondHand['high_value'];
                }

                if ($firstHand['low_value'] != $secondHand['low_value'])
                {
                    return $firstHand['low_value'] <=> $secondHand['low_value'];
                }

                return $firstHand['kicker'] <=> $secondHand['kicker'];
            case PokerHandEvaluatorService::STRAIGHT:
            case PokerHandEvaluatorService::STRAIGHT_FLUSH:
                return $firstHand['value'] <=> $secondHand['value'];
            case PokerHandEvaluatorService::FULL_HOUSE:
                if ($firstHand['three_of_a_kind_value'] != $secondHand['three_of_a_kind_value'])
                {
                    return $firstHand['three_of_a_kind_value'] <=> $secondHand['three_of_a_kind_value'];
                }

                return $firstHand['pair_value'] <=> $secondHand['pair_value'];
            case PokerHandEvaluatorService::FOUR_OF_A_KIND:
                if ($firstHand['value'] != $secondHand['value'])
                {
                    return $firstHand['value'] <=> $secondHand['value'];
                }

                return $firstHand['kicker'] <=> $secondHand['kicker'];
        }

        return 0;
    }

    private function compareTwoArrays(array $firstArray, array $secondArray) : int
    {
        for ($i = 0; $i < count($firstArray); $i++)
        {
            if ($firstArray[$i] == $secondArray[$i])
            {
                continue;
            }

            return $firstArray[$i] <=> $secondArray[$i];
        }

        return 0;
    }
}