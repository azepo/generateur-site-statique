<?php

declare(strict_types=1);

namespace App\Twig;

use App\Generateur\Assets;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Fonction Twig « asset_site('accueil.css') » : chemin publié d'un fichier statique, avec son empreinte.
 */
final class AssetsExtension extends AbstractExtension
{
    public function __construct(private readonly Assets $assets)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('asset_site', $this->assets->chemin(...))];
    }
}
