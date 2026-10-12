<?php

declare(strict_types=1);

namespace App\Contenu;

/**
 * Résultat du rendu d'un texte Markdown.
 */
final readonly class Rendu
{
    /**
     * @param list<Titre> $titres titres portant un identifiant, dans l'ordre du texte
     */
    public function __construct(
        public string $html,
        public array $titres = [],
    ) {
    }

    /**
     * @return list<Titre>
     */
    public function titresDeNiveau(int $niveau): array
    {
        return array_values(array_filter($this->titres, static fn (Titre $titre): bool => $titre->niveau === $niveau));
    }
}
