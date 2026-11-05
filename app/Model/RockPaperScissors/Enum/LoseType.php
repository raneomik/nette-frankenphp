<?php

namespace App\Model\RockPaperScissors\Enum;

enum LoseType: string
{
    case Crushed = 'crushed by';
    case Cut = 'cut by';
    case Covered = 'covered by';
    case Eaten = 'eaten by';
    case Disproved = 'disproven by';
    case Decapitated = 'decapitated by';
    case Poisoned = 'poisoned by';
    case Vaporized = 'vaporized by';
    case Smashed = 'smashed by';
}
