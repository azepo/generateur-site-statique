<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/packages/rgaa-check/src', __DIR__.'/packages/rgaa-check/tests', __DIR__.'/packages/rgaa-check-behat/src', __DIR__.'/packages/rgaa-check-behat/tests'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        // Convention du projet : typage strict dans tous les fichiers PHP
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
