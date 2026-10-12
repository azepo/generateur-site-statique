<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Mesure d'audience Matomo, facultative : sans cette section dans config/site.yaml,
 * aucune page ne charge de script de statistiques.
 */
final readonly class Matomo
{
    /**
     * @param string $url    adresse du serveur Matomo, avec la barre finale : « //stats.exemple.org/ »
     * @param string $siteId identifiant du site dans Matomo
     */
    public function __construct(
        public string $url,
        public string $siteId,
    ) {
    }
}
