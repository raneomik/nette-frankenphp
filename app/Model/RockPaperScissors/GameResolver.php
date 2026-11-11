<?php

namespace App\Model\RockPaperScissors;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Enum\Result;
use App\Model\RockPaperScissors\Value\DuelState;
use Nette\Database\Table\ActiveRow;

final class GameResolver
{
    /**
     * Duel state as seen by $viewerId: the opponent, whether they already played the current round, and their hand once the round is complete.
     *
     * @param array<int, array<string, Hand>> $rounds round => userId => hand
     */
    public function resolveDuel(
        array $rounds,
        string $me,
        ActiveRow $opponent,
        int $myRound,
        int $theirRound,
    ): DuelState {

        $them = $opponent->userId;
        // score on complete rounds only
        $myScore = $theirScore = 0;
        foreach ($rounds as $hands) {
            if (isset($hands[$me], $hands[$them])) {
                match (Result::fromGame($hands[$me], $hands[$them])) {
                    Result::Win => $myScore++,
                    Result::Lose => $theirScore++,
                    Result::Draw => null,
                };
            }
        }

        $complete = 0 < $myRound && $myRound === $theirRound;

        return new DuelState(
            $opponent->username ?: 'Computer',
            $theirRound > $myRound,
            $complete ? $rounds[$myRound][$them] : null,
            $complete ? $rounds[$myRound][$me] : null,
            $myScore,
            $theirScore,
        );
    }
}
