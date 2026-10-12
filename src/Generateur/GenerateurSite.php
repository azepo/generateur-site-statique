<?php

declare(strict_types=1);

namespace App\Generateur;

use App\Contenu\RenduMarkdown;
use App\Site\ChargeurSite;
use App\Site\Page;
use App\Site\ProfilRendu;
use App\Site\TypePage;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Génère le site statique dans le dossier de sortie.
 */
final readonly class GenerateurSite
{
    public function __construct(
        private ChargeurSite $chargeur,
        private RenduMarkdown $markdown,
        private Assets $assets,
        private Medias $medias,
        private Environment $twig,
        private Filesystem $fichiers,
        private string $dossierDist,
    ) {
    }

    /**
     * @param bool $avecBrouillons générer aussi les contenus non publiés (prévisualisation)
     *
     * @return list<string> chemins des fichiers générés
     */
    public function generer(bool $avecBrouillons = false): array
    {
        $site = $this->chargeur->charger($avecBrouillons);

        // Le dossier est vidé à chaque génération : aucun fichier obsolète ne peut être publié.
        $this->fichiers->remove($this->dossierDist);
        $this->fichiers->mkdir($this->dossierDist);

        $ecrits = [...$this->assets->publier($this->dossierDist), ...$this->medias->publier($this->dossierDist)];

        foreach ($site->pages as $page) {
            $rendu = $this->markdown->rendre($page->corps, $page->profilRendu(), $site->hote(), $page->paragraphes);
            $ecrits[] = $this->ecrire($page->fichier(), $this->twig->render($this->gabarit($page), [
                'site' => $site,
                'page' => $page,
                'contenu' => $rendu->html,
                'titres' => $rendu->titres,
            ]));
        }

        $ecrits[] = $this->ecrire('sitemap.xml', $this->twig->render('sitemap.xml.twig', ['site' => $site]));
        $ecrits[] = $this->ecrire('robots.txt', $this->twig->render('robots.txt.twig', ['site' => $site]));

        return $ecrits;
    }

    private function gabarit(Page $page): string
    {
        if (ProfilRendu::Memo === $page->profilRendu()) {
            return 'memo.html.twig';
        }

        return match ($page->type) {
            TypePage::Accueil => 'accueil.html.twig',
            TypePage::Plan => 'plan_du_site.html.twig',
            TypePage::Article => 'article.html.twig',
            TypePage::Page => 'page.html.twig',
        };
    }

    private function ecrire(string $nom, string $contenu): string
    {
        $chemin = $this->dossierDist.'/'.$nom;
        $this->fichiers->dumpFile($chemin, $contenu);

        return $chemin;
    }
}
