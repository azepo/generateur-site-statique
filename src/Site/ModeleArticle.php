<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Valeurs proposées par « article:new » dans l'en-tête d'un nouvel article tiré d'un PDF
 * (section « nouvel_article » de config/site.yaml). Toutes sont facultatives.
 */
final readonly class ModeleArticle
{
    /**
     * @param string|null $publication nom de la publication d'où viennent les articles : « Mon magazine »
     * @param string|null $url         adresse de cette publication
     * @param string|null $traducteur  nom affiché dans « Traduit par »
     */
    public function __construct(
        public ?string $publication = null,
        public ?string $url = null,
        public ?string $traducteur = null,
    ) {
    }
}
