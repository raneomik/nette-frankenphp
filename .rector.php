<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/www',
    ])
    // uncomment to reach your current PHP version
     ->withPreparedSets(
         namedArgs: true,
    )
     ->withPhpSets(php85: true)
    ->withTypeCoverageLevel(10)
    ->withDeadCodeLevel(10)
    ->withCodeQualityLevel(10);
