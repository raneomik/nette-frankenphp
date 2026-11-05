<?php

namespace App\Model\RockPaperScissors\Value;

readonly class WaitingRoom
{
    public function __construct(
        public string $id,
        public string $lonelyPlayer,
    ) {
    }
}
