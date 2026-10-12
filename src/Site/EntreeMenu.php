<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Une entrée du menu principal : soit un lien direct vers une page, soit une rubrique (liste déroulante).
 */
final readonly class EntreeMenu
{
    /**
     * @param list<Page> $pages pages de la liste déroulante (vide pour un lien direct)
     */
    public function __construct(
        public ?Page $page = null,
        public ?Rubrique $rubrique = null,
        public array $pages = [],
    ) {
    }

    public function estListe(): bool
    {
        return null !== $this->rubrique;
    }
}
