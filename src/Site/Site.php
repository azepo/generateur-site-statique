<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Le site : ses réglages, ses rubriques et ses pages publiées.
 */
final readonly class Site
{
    /**
     * @param array<string, Rubrique> $rubriques indexées par identifiant
     * @param array<string, Page>     $pages     pages publiées, indexées par slug, dans l'ordre d'affichage
     * @param list<EntreeMenu>        $menu
     */
    public function __construct(
        public string $nom,
        public string $url,
        public int $annee,
        public array $rubriques,
        public array $pages,
        public array $menu,
        public ?Matomo $matomo = null,
        public ModeleArticle $modeleArticle = new ModeleArticle(),
    ) {
    }

    public function page(string $slug): Page
    {
        return $this->pages[$slug] ?? throw new \InvalidArgumentException(sprintf('Page inconnue : « %s ».', $slug));
    }

    /** Adresse publique d'une page. L'accueil est servi à la racine du site. */
    public function urlDe(Page $page): string
    {
        return $this->url.'/'.($page->estAccueil() ? '' : $page->fichier());
    }

    /** Nom de domaine du site, sans « https:// ». */
    public function hote(): string
    {
        $hote = parse_url($this->url, \PHP_URL_HOST);

        return \is_string($hote) ? $hote : throw new \LogicException(sprintf('L\'adresse du site « %s » est invalide.', $this->url));
    }

    /** Titre de l'onglet du navigateur. */
    public function titreOnglet(Page $page): string
    {
        return $page->titrePage ?? $page->titre.' – '.$this->hote();
    }

    /**
     * Toutes les pages publiées, rubrique par rubrique (sitemap.xml, robots.txt).
     *
     * @return list<Page>
     */
    public function toutesLesPages(): array
    {
        $pages = [];
        foreach ($this->rubriques as $rubrique) {
            foreach ($this->pages as $page) {
                if ($page->rubrique === $rubrique->id) {
                    $pages[] = $page;
                }
            }
        }

        return $pages;
    }

    /**
     * Sections du plan du site : chaque rubrique avec ses pages, dans l'ordre de déclaration.
     *
     * @return list<array{rubrique: Rubrique, pages: list<Page>}>
     */
    public function plan(): array
    {
        $sections = [];
        foreach ($this->rubriques as $rubrique) {
            $pages = array_values(array_filter(
                $this->pages,
                static fn (Page $page): bool => $page->rubrique === $rubrique->id && $page->dansPlan,
            ));
            if ([] !== $pages) {
                $sections[] = ['rubrique' => $rubrique, 'pages' => $pages];
            }
        }

        return $sections;
    }
}
