<?php

namespace PokerEngine\Service;

class DeckService
{
    private $deck = [
        'card_diamond_2',
        'card_diamond_3',
        'card_diamond_4',
        'card_diamond_5',
        'card_diamond_6',
        'card_diamond_7',
        'card_diamond_8',
        'card_diamond_9',
        'card_diamond_10',
        'card_diamond_11',
        'card_diamond_12',
        'card_diamond_13',
        'card_diamond_14',

        'card_hearts_2',
        'card_hearts_3',
        'card_hearts_4',
        'card_hearts_5',
        'card_hearts_6',
        'card_hearts_7',
        'card_hearts_8',
        'card_hearts_9',
        'card_hearts_10',
        'card_hearts_11',
        'card_hearts_12',
        'card_hearts_13',
        'card_hearts_14',

        'card_clubs_2',
        'card_clubs_3',
        'card_clubs_4',
        'card_clubs_5',
        'card_clubs_6',
        'card_clubs_7',
        'card_clubs_8',
        'card_clubs_9',
        'card_clubs_10',
        'card_clubs_11',
        'card_clubs_12',
        'card_clubs_13',
        'card_clubs_14',

        'card_spades_2',
        'card_spades_3',
        'card_spades_4',
        'card_spades_5',
        'card_spades_6',
        'card_spades_7',
        'card_spades_8',
        'card_spades_9',
        'card_spades_10',
        'card_spades_11',
        'card_spades_12',
        'card_spades_13',
        'card_spades_14',
    ];
    private $activeDeck;

    public function shuffleDeck()
    {
        shuffle($this->deck);

        $this->activeDeck = $this->deck;
    }

    public function pop()
    {
        return array_pop($this->activeDeck);
    }
}