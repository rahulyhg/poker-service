<?php

namespace PokerEngine\Service;

class PokerHandEvaluatorService
{
    const HIGH_CARD = 1;
    const ONE_PAIR = 2;
    const TWO_PAIR = 3;
    const THREE_OF_A_KIND = 4;
    const STRAIGHT = 5;
    const FLUSH = 6;
    const FULL_HOUSE = 7;
    const FOUR_OF_A_KIND = 8;
    const STRAIGHT_FLUSH = 9;
    const ROYAL_FLUSH = 10;

    private $cards = [];

    public function evaluate(array $cards) : array
    {
        $this->cards = [];
        $this->convertCards($cards);
        $this->sortByValue();

        if ($result = $this->isRoyalFlush())
        {
            return $result;
        }

        if ($result = $this->isStraightFlush())
        {
            return $result;
        }

        if ($result = $this->isFourOfAKind())
        {
            return $result;
        }

        if ($result = $this->isFullHouse())
        {
            return $result;
        }

        if ($result = $this->isFlush())
        {
            return $result;
        }

        if ($result = $this->isStraight())
        {
            return $result;
        }

        if ($result = $this->isThreeOfAKind())
        {
            return $result;
        }

        if ($result = $this->isTwoPair())
        {
            return $result;
        }

        if ($result = $this->isPair())
        {
            return $result;
        }

        return $this->isHighCard();
    }

    private function convertCards(array $cards)
    {
        foreach ($cards as $card) {
            $parts = explode('_', $card);

            $this->cards[] = [
                'id' => $card,
                'color' => $parts[1],
                'value' => (int)$parts[2],
            ];
        }
    }

    private function sortByValue()
    {
        usort($this->cards, function($a, $b) {
            return $b['value'] - $a['value'];
        });
    }

    private function isRoyalFlush()
    {
        $straightFlush = $this->isStraightFlush();

        if (!$straightFlush || $straightFlush['value'] < 13)
        {
            return false;
        }

        return ['rank' => self::ROYAL_FLUSH];
    }

    private function isStraightFlush()
    {
        $flush = $this->findFlush();

        if (!$flush)
        {
            return false;
        }

        $straight = $this->isStraight($flush);

        if (!$straight)
        {
            return false;
        }

        return [
            'rank' => self::STRAIGHT_FLUSH,
            'value' => $straight['value'],
        ];
    }

    private function isFourOfAKind()
    {
        $fourOfAKind = $this->findCardsWithSameValue($this->cards, 4);

        if (!$fourOfAKind)
        {
            return false;
        }

        return [
            'rank' => self::FOUR_OF_A_KIND,
            'value' => $fourOfAKind[0]['value'],
            'kicker' => current($this->selectHighestValues($this->excludeCards($fourOfAKind), 1)),
        ];
    }

    private function isFullHouse()
    {
        $threeOfAKind = $this->findCardsWithSameValue($this->cards, 3);

        if (!$threeOfAKind)
        {
            return false;
        }

        $pair = $this->findCardsWithSameValue($this->excludeCards($threeOfAKind));

        if (!$pair)
        {
            return false;
        }

        return [
            'rank' => self::FULL_HOUSE,
            'three_of_a_kind_value' => $threeOfAKind[0]['value'],
            'pair_value' => $pair[0]['value'],
        ];
    }

    private function isFlush()
    {
        $flush = $this->findFlush();

        if (!$flush)
        {
            return false;
        }

        return [
            'rank' => self::FLUSH,
            'values' => $this->selectHighestValues($flush),
        ];
    }

    private function findFlush()
    {
        $cardsOfColors = [
            'diamond' => [],
            'hearts' => [],
            'clubs' => [],
            'spades' => [],
        ];

        foreach ($this->cards as $card)
        {
            $cardsOfColors[$card['color']][] = $card;
        }

        foreach ($cardsOfColors as $cardsOfSpecifiedColor)
        {
            if (count($cardsOfSpecifiedColor) >= 5)
            {
                return $cardsOfSpecifiedColor;
            }
        }

        return false;
    }

    private function isStraight($cards = null)
    {
        $cards = $cards ?? $this->cards;
        $cardsInStraight = [];

        foreach ($cards as $card)
        {
            if (empty($cardsInStraight) || end($cardsInStraight)['value'] - 1 == $card['value'])
            {
                $cardsInStraight[] = $card;
            } else {
                $cardsInStraight = [$card];
            }

            if (count($cardsInStraight) == 5)
            {
                return [
                    'rank' => self::STRAIGHT,
                    'value' => $cardsInStraight[0]['value'],
                ];
            }
        }

        return false;
    }

    private function isThreeOfAKind()
    {
        $threeOfAKind = $this->findCardsWithSameValue($this->cards, 3);

        if (!$threeOfAKind)
        {
            return false;
        }

        return [
            'rank' => self::THREE_OF_A_KIND,
            'value' => $threeOfAKind[0]['value'],
            'kickers' => $this->selectHighestValues($this->excludeCards($threeOfAKind), 2),
        ];
    }

    private function isTwoPair()
    {
        $highPair = $this->findCardsWithSameValue($this->cards);

        if (!$highPair)
        {
            return false;
        }

        $lowPair = $this->findCardsWithSameValue($this->excludeCards($highPair));

        if (!$lowPair)
        {
            return false;
        }

        return [
            'rank' => self::TWO_PAIR,
            'high_value' => $highPair[0]['value'],
            'low_value' => $lowPair[0]['value'],
            'kicker' => current($this->selectHighestValues($this->excludeCards(array_merge($highPair, $lowPair)), 1)),
        ];
    }

    private function isPair()
    {
        $pair = $this->findCardsWithSameValue($this->cards);

        if (!$pair)
        {
            return false;
        }

        return [
            'rank' => self::ONE_PAIR,
            'value' => $pair[0]['value'],
            'kickers' => $this->selectHighestValues($this->excludeCards($pair), 3),
        ];
    }

    private function isHighCard() : array
    {
        return [
            'rank' => self::HIGH_CARD,
            'values' => $this->selectHighestValues($this->cards),
        ];
    }

    private function findCardsWithSameValue(array $cards, $neededCount = 2)
    {
        $sameCards = [];

        foreach ($cards as $card)
        {
            if (empty($sameCards) || $sameCards[0]['value'] == $card['value'])
            {
                $sameCards[] = $card;
            } else {
                $sameCards = [$card];
            }

            if (count($sameCards) == $neededCount)
            {
                return $sameCards;
            }
        }

        return false;
    }

    private function excludeCards(array $cardsToExclude) : array
    {
        $remainedCards = [];

        foreach ($this->cards as $card)
        {
            if (in_array($card, $cardsToExclude))
            {
                continue;
            }

            $remainedCards[] = $card;
        }

        return $remainedCards;
    }

    private function selectHighestValues(array $cards, int $count = 5) : array
    {
        $cardValues = [];

        foreach ($cards as $card)
        {
            $cardValues[] = $card['value'];

            if (count($cardValues) == $count) {
                break;
            }
        }

        return $cardValues;
    }
}