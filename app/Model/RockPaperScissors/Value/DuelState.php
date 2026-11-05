<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Enum\LoseType;
use App\Model\RockPaperScissors\Enum\WinType;

readonly class DuelState
{
    public function __construct(
        public ?string $opponent = null,
        public bool $opponentPlayed = false,
        public ?Hand $revealed = null, // opponent's hand in the last complete round
        public ?Hand $mine = null,     // my hand in that same round
        public int $myScore = 0,
        public int $theirScore = 0,
        public bool $solo = false,     // game against the computer, no cumulative score
    ) {
    }

    /**
     * how my hand won (WinType) or lost (LoseType) in the last round; null = draw or nothing revealed
     */
    public function outcome(): null|WinType|LoseType
    {
        return $this->mine && $this->revealed ? $this->mine->game($this->revealed) : null;
    }
}
