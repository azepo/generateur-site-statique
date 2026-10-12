<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Qui a traduit et relu un article.
 */
final readonly class Traduction
{
    public function __construct(
        public ?string $traducteur = null,
        public ?string $relecteur = null,
        public ?string $relecteurUrl = null,
    ) {
    }
}
