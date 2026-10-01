<?php

declare(strict_types=1);

/**
 * Carried over from v1's ruleset, which the team already reads without friction, plus
 * the strict-types declaration required everywhere here.
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/bin',
        __DIR__ . '/config',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'concat_space' => ['spacing' => 'one'],
        'single_line_throw' => false,
        'yoda_style' => false,
        'phpdoc_align' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
    ])
    ->setFinder($finder);
