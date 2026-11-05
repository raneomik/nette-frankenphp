<?php

namespace App\Model\Service;

use Faker\Factory;
use Faker\Generator;
use NavisBorealis\WonderwordsPhp\Faker\WonderWordsProvider;

final readonly class Randomiser
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->addProvider(new WonderWordsProvider($this->faker));
    }

    public function username(): string
    {
        return sprintf(
            '%s %s',
            ucfirst($this->faker->wonderWordsAdjective()),
            ucfirst($this->faker->wonderWordsAnimal()),
        );
    }

    /**
     * UUID v4 from random_bytes(): Faker's uuid derives from mt_rand, which is not a secure source
     * (player ids name their private Mercure topic, room ids give access to a game)
     */
    public function id(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr(\ord($bytes[6]) & 0x0F | 0x40); // version 4
        $bytes[8] = \chr(\ord($bytes[8]) & 0x3F | 0x80); // RFC 4122 variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
