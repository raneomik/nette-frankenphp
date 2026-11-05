<?php

namespace App\Model\RockPaperScissors\Enum;

enum WinType: string
{
    case Crushes = 'crushes';
    case Cuts = 'cuts';
    case Covers = 'covers';
    case Eats = 'eats';
    case Disproves = 'disproves';
    case Decapitates = 'decapitates';
    case Poisons = 'poisons';
    case Vaporizes = 'vaporizes';
    case Smashes = 'smashes';
}
