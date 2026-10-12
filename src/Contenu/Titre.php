<?php

declare(strict_types=1);

namespace App\Contenu;

/**
 * Un titre du texte qui porte un identifiant : une entrée possible du sommaire de la page.
 */
final readonly class Titre
{
    public function __construct(
        public int $niveau,
        public string $id,
        public string $libelle,
    ) {
    }
}
