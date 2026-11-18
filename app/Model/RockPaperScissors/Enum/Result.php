<?php

namespace App\Model\RockPaperScissors\Enum;

enum Result: string
{
    case Win = 'win';

    case Lose = 'lose';

    case Draw = 'draw';

    public static function fromGame(Hand $player, Hand $opponent): self
    {
        return match (true) {
            $player->beats($opponent) => self::Win,
            $opponent->beats($player) => self::Lose,
            default => self::Draw,
        };
    }
}
