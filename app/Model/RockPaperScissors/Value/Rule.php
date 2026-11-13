<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;

readonly class Rule
{
    public function __construct(
        public Hand $player,
        public Hand $opponent,
    ) {
    }

    public function isAWin(): bool
    {
        return $this->player->beats($this->opponent);
    }
}
