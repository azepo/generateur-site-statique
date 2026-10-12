<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Un groupe de pages : une liste déroulante du menu, une section de l'accueil et du plan du site.
 */
final readonly class Rubrique
{
    public function __construct(
        public string $id,
        public string $titrePlan,
        public ?string $titreMenu = null,
        public ?string $idMenu = null,
        public ?string $introAccueil = null,
    ) {
    }
}
