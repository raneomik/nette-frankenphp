<?php

namespace App\Model\RockPaperScissors\Enum;

enum Result: string
{
    case Win = 'win';
    case Lose = 'lose';
    case Draw = 'draw';

    public static function fromGame(Hand $player, Hand $opponent): self
    {
        // no $game::class: game() returns null on a draw
        return match (true) {
            $player->game($opponent) instanceof WinType => self::Win,
            $player->game($opponent) instanceof LoseType => self::Lose,
            default => self::Draw,
        };
    }
}
