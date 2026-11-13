<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Enum\Result;

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
     * my result in the last round; null = nothing revealed
     */
    public function outcome(): ?Result
    {
        return $this->mine && $this->revealed ? Result::fromGame($this->mine, $this->revealed) : null;
    }
}
