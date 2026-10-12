<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Publication d'origine d'un article traduit.
 */
final readonly class Source
{
    public function __construct(
        public string $publication,
        public ?string $url = null,
        public ?string $auteur = null,
        public ?int $volume = null,
        public ?int $numero = null,
        public ?string $date = null,
        public ?int $episode = null,
        public ?string $auteurNote = null,
    ) {
    }

    /** « Volume 20 - Numéro 2 », « Episode 31 », ou rien si la source n'a pas de numérotation. */
    public function reference(): ?string
    {
        if (null !== $this->volume && null !== $this->numero) {
            return sprintf('Volume %d - Numéro %d', $this->volume, $this->numero);
        }

        return null !== $this->episode ? sprintf('Episode %d', $this->episode) : null;
    }
}
