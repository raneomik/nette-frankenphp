<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Enum\LoseType;
use App\Model\RockPaperScissors\Enum\WinType;

readonly class Rule
{
    public function __construct(
        public Hand $player,
        public WinType|LoseType $game,
        public Hand $opponent,
    ) {
    }

    public function isAWin(): bool
    {
        return $this->game instanceof WinType;
    }
}
