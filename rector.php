<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

/**
 * Modernisation, targeting the PHP the fleet actually runs.
 *
 * PHP_81 rather than the version CI runs: the server is on 8.1, and rewriting code to
 * newer syntax would strand a deploy there.
 *
 * NOT CURRENTLY IN THE GATE SET. rector/rector 2.4.6 crashes on startup against
 * phpstan/phpstan 2.2.8 — it reaches into PHPStan internals through a PrivatesAccessor
 * and PHPStan\Parser\RichParser no longer has the $container property it expects:
 *
 *     MissingPrivatePropertyException: Property "$container" was not found in
 *     "PHPStan\Parser\RichParser"
 *
 * Both are already at the newest versions their constraints allow, and PHPStan cannot be
 * moved down because szepeviktor/phpstan-wordpress ^2.0 requires ^2.2. So there is no
 * combination available today that satisfies both, and this is an upstream problem
 * rather than a configuration one.
 *
 * The config is kept, ready, so that re-enabling it is one line in composer.json's
 * `lint` script and one step in CI once Rector releases a build that tolerates PHPStan
 * 2.2. Meanwhile PHPStan at level 8 and php-cs-fixer cover much of the same ground.
 *
 * See issue #12.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
    ])
    ->withSets([
        SetList::PHP_81,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    );
