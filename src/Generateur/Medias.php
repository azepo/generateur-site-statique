<?php

declare(strict_types=1);

namespace App\Generateur;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Images des contenus.
 *
 * Chaque article range ses images dans son dossier « media/ ». Elles sont publiées dans le dossier
 * « media/ » du site, en gardant leur chemin : content/articles/X/media/cqrs/figure1.jpg -> media/cqrs/figure1.jpg,
 * ce qui correspond à l'adresse « ./media/cqrs/figure1.jpg » écrite dans le texte.
 */
final readonly class Medias
{
    public function __construct(
        private string $dossierContenu,
        private Filesystem $fichiers,
    ) {
    }

    /**
     * @return list<string> chemins des fichiers écrits
     */
    public function publier(string $dossierDist): array
    {
        $dossiers = (new Finder())->directories()->in($this->dossierContenu)->path('/^(articles\/[^\/]+|pages)\/media$/');
        $ecrits = [];
        $origines = [];

        foreach ($dossiers as $dossier) {
            foreach ((new Finder())->files()->in($dossier->getPathname())->sortByName() as $fichier) {
                $relatif = str_replace('\\', '/', $fichier->getRelativePathname());
                if (isset($origines[$relatif]) && hash_file('sha256', $origines[$relatif]) !== hash_file('sha256', $fichier->getPathname())) {
                    throw new \RuntimeException(sprintf('Deux images différentes portent le même nom « media/%s » : %s et %s.', $relatif, $origines[$relatif], $fichier->getPathname()));
                }
                $origines[$relatif] = $fichier->getPathname();
                $cible = $dossierDist.'/media/'.$relatif;
                $this->fichiers->copy($fichier->getPathname(), $cible, true);
                $ecrits[] = $cible;
            }
        }
        sort($ecrits);

        return array_values(array_unique($ecrits));
    }
}
