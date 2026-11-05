<?php

namespace App\Model\RockPaperScissors\Value;

readonly class GameRecord
{
    public \DateTime $createdAt;

    public function __construct(
        public string $room,
        public string $userId,
        public string $username,
        public string $move,
        public int $round,
    ) {
        $this->createdAt = new \DateTime();
    }
}
