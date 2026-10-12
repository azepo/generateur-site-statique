<?php

declare(strict_types=1);

namespace App\Controle;

use Azepo\RgaaCheck\Checker;
use Azepo\RgaaCheck\Report;

/**
 * Contrôle d'accessibilité des pages du site, avec les réglages propres au site.
 *
 * Les règles elles-mêmes sont dans le paquet azepo/rgaa-check (packages/rgaa-check/).
 * Elles couvrent ce qu'un programme peut vérifier : elles ne remplacent pas les tests
 * au clavier, au zoom et avec un lecteur d'écran (voir context/audit_rgaa.md).
 */
final readonly class ControleurAccessibilite
{
    /** Fichiers de vérification de propriété (Google Search Console…) : ce ne sont pas des pages du site. */
    private const array HORS_CONTROLE = ['/^google[0-9a-f]+\.html$/'];

    public function __construct(private Checker $checker)
    {
    }

    /** Contrôle les pages à la racine du dossier. Les sous-dossiers (assets/, media/, formations/) ne contiennent pas de pages du site. */
    public function controler(string $dossier): Report
    {
        return $this->checker->checkDirectory($dossier, recursive: false, exclude: self::HORS_CONTROLE);
    }
}
