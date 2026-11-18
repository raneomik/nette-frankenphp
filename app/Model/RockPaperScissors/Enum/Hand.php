<?php

namespace App\Model\RockPaperScissors\Enum;

enum Hand: string
{
    case Rock = 'rock';
    case Paper = 'paper';
    case Scissors = 'scissors';
    case Lizard = 'lizard';
    case Spock = 'spock';

    public function beats(self $opponent): bool
    {
        return in_array($opponent, match ($this) {
            self::Rock => [self::Scissors, self::Lizard],
            self::Paper => [self::Rock, self::Spock],
            self::Scissors => [self::Paper, self::Lizard],
            self::Lizard => [self::Paper, self::Spock],
            self::Spock => [self::Scissors, self::Rock],
        }, true);
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Rock => '👊',
            self::Paper => '✋',
            self::Scissors => '✌️',
            self::Lizard => '🤏',
            self::Spock => '🖖',
        };
    }
}
