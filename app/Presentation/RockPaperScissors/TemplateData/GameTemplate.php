<?php

declare(strict_types=1);

namespace App\Presentation\RockPaperScissors\TemplateData;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Value\DuelState;
use App\Model\RockPaperScissors\Value\RulesMap;
use Nette;

final class GameTemplate extends Nette\Bridges\ApplicationLatte\Template
{
    public Nette\Security\User $user;

    public array $flashes;

    public array $availableHands = [];

    public RulesMap $rulesMap;

    public iterable $rooms = [];

    public string $room = 'lobby';

    public string $newRoom = '';

    public string $pageId = ''; // identifies this page for the leave! signal

    // duel: stay empty until the opponent state is read from the database (step 3)
    public DuelState|null $duelState = null;

    public function initData(): void
    {
        $this->availableHands = Hand::cases();
        $this->rulesMap = new RulesMap();
    }
}
