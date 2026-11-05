<?php

namespace App\Model\RockPaperScissors\Enum;

enum Hand: string
{
    case Rock = 'rock';
    case Paper = 'paper';
    case Scissors = 'scissors';
    case Lizard = 'lizard';
    case Spock = 'spock';

    public function game(self $opponent): null|WinType|LoseType
    {
        return match($this) {
            self::Rock => match ($opponent) {
                self::Scissors, self::Lizard => WinType::Crushes,
                default => $this->beatenBy($opponent),
            },
            self::Paper => match ($opponent) {
                self::Rock => WinType::Covers,
                self::Spock => WinType::Disproves,
                default => $this->beatenBy($opponent),
            },
            self::Scissors => match ($opponent) {
                self::Paper => WinType::Cuts,
                self::Lizard => WinType::Decapitates,
                default => $this->beatenBy($opponent),
            },
            self::Lizard => match($opponent) {
                self::Paper => WinType::Eats,
                self::Spock => WinType::Poisons,
                default => $this->beatenBy($opponent),
            },
            self::Spock => match($opponent) {
                self::Scissors => WinType::Smashes,
                self::Rock => WinType::Vaporizes,
                default => $this->beatenBy($opponent),
            },
        };
    }

    private function beatenBy(self $opponent): ?LoseType
    {
        return match($opponent) {
            self::Rock => match ($this) {
                self::Scissors, self::Lizard => LoseType::Crushed,
                default => null,
            },
            self::Paper => match ($this) {
                self::Rock => LoseType::Covered,
                self::Spock => LoseType::Disproved,
                default => null,
            },
            self::Scissors => match ($this) {
                self::Paper => LoseType::Cut,
                self::Lizard => LoseType::Decapitated,
                default => null,
            },
            self::Lizard => match($this) {
                self::Paper => LoseType::Eaten,
                self::Spock => LoseType::Poisoned,
                default => null,
            },
            self::Spock => match($this) {
                self::Scissors => LoseType::Smashed,
                self::Rock => LoseType::Vaporized,
                default => null,
            },
        };
    }

    /** display name, mid-sentence: "Spock" is a proper noun, the others stay lower case (|firstUpper at the start of a sentence) */
    public function label(): string
    {
        return match($this) {
            self::Spock => 'Spock',
            default => $this->value,
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Rock => '👊',
            self::Paper => '✋',
            self::Scissors => '✌️',
            self::Lizard => '🤏',
            self::Spock => '🖖',
        };
    }
}
