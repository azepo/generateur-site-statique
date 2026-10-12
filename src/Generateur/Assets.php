<?php

declare(strict_types=1);

namespace App\Generateur;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Fichiers statiques du site (CSS, JavaScript).
 *
 * Chaque fichier est publié avec une empreinte de son contenu dans son nom
 * (accueil.css -> assets/accueil.3f2a1c9e.css). Quand le fichier change, son nom change :
 * le navigateur ne peut donc plus afficher une ancienne version restée en cache.
 */
final class Assets
{
    private const string DOSSIER_PUBLIC = 'assets';

    /** @var array<string, string>|null nom du fichier source => chemin publié */
    private ?array $chemins = null;

    public function __construct(
        private readonly string $dossierAssets,
        private readonly Filesystem $fichiers,
    ) {
    }

    /** Chemin publié d'un fichier, relatif à la racine du site. */
    public function chemin(string $nom): string
    {
        return $this->chemins()[$nom]
            ?? throw new \InvalidArgumentException(sprintf('Fichier statique introuvable : « %s » (dossier %s).', $nom, $this->dossierAssets));
    }

    /**
     * Copie tous les fichiers statiques dans le dossier du site généré.
     *
     * @return list<string> chemins des fichiers écrits
     */
    public function publier(string $dossierDist): array
    {
        $ecrits = [];
        foreach ($this->chemins() as $nom => $chemin) {
            $this->fichiers->copy($this->dossierAssets.'/'.$nom, $dossierDist.'/'.$chemin, true);
            $ecrits[] = $dossierDist.'/'.$chemin;
        }

        return $ecrits;
    }

    /**
     * @return array<string, string>
     */
    private function chemins(): array
    {
        if (null !== $this->chemins) {
            return $this->chemins;
        }

        $this->chemins = [];
        foreach ((new Finder())->files()->in($this->dossierAssets)->sortByName() as $fichier) {
            $nom = str_replace('\\', '/', $fichier->getRelativePathname());
            $empreinte = substr(hash('sha256', $fichier->getContents()), 0, 8);
            $extension = $fichier->getExtension();
            $base = '' === $extension ? $nom : substr($nom, 0, -\strlen($extension) - 1);
            $this->chemins[$nom] = self::DOSSIER_PUBLIC.'/'.$base.'.'.$empreinte.('' === $extension ? '' : '.'.$extension);
        }

        return $this->chemins;
    }
}
