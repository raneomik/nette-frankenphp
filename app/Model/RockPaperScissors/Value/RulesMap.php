<?php

namespace App\Model\RockPaperScissors\Value;

use App\Model\RockPaperScissors\Enum\Hand;
use Traversable;

/**
 * @implements \IteratorAggregate<int, Rule>
 */
readonly class RulesMap implements \IteratorAggregate
{
    /**
     * @var list<Rule>
     */
    public array $rules;

    public function __construct()
    {
        $this->rules = $this->populate();
    }

    /**
     * @return list<Rule>
     */
    private function populate(): array
    {
        $winningRules = [];
        $losingRules = [];
        foreach (Hand::cases() as $player) {
            foreach (Hand::cases() as $opponent) {
                if ($opponent === $player) {
                    continue;
                }

                $rule = new Rule($player, $opponent);
                if ($rule->isAWin()) {
                    $winningRules[] = $rule;
                } else {
                    $losingRules[] = $rule;
                }
            }
        }

        return array_merge($winningRules, $losingRules);
    }

    public function getIterator(): Traversable
    {
        yield from $this->rules;
    }
}
