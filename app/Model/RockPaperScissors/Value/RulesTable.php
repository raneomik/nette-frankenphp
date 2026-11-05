<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Enum\Result;
use Traversable;

readonly class RulesTable implements \IteratorAggregate
{
    public array $rules;

    public function __construct()
    {
        $this->rules = $this->populate();
    }

    private function populate(): array
    {
        $rules = [];
        foreach (Hand::cases() as $player) {
            foreach (Hand::cases() as $opponent) {
                if ($opponent === $player) {
                    continue;
                }

                $game = $player->game($opponent);
                if (null === $game) {
                    continue;
                }

                $rules[] = new Rule($player, $game, $opponent);
            }
        }

        usort(
            $rules,
            fn (Rule $a, Rule $b): int => match (Result::fromGame($a->player, $a->opponent)) {
                Result::Win => -1,
                Result::Lose => 1,
                Result::Draw => 0,
            }
        );

        return $rules;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->rules);
    }
}
