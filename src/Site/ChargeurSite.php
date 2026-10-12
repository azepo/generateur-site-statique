<?php

declare(strict_types=1);

namespace App\Site;

use App\Contenu\DepotContenu;
use Symfony\Component\Yaml\Yaml;

/**
 * Construit le Site à partir de la configuration YAML et des fichiers de contenu.
 */
final readonly class ChargeurSite
{
    public function __construct(
        private string $fichierSite,
        private DepotContenu $depot,
    ) {
    }

    /**
     * @param bool $avecBrouillons inclure les contenus qui ne sont pas encore publiés (prévisualisation)
     */
    public function charger(bool $avecBrouillons = false): Site
    {
        $config = Yaml::parseFile($this->fichierSite);
        if (!\is_array($config)) {
            throw new \RuntimeException(sprintf('Le fichier « %s » est vide ou invalide.', $this->fichierSite));
        }

        $rubriques = [];
        foreach ($this->section($config, 'rubriques') as $id => $donnees) {
            $rubriques[(string) $id] = new Rubrique(
                id: (string) $id,
                titrePlan: $this->texte($donnees, 'titre_plan', "rubriques.$id"),
                titreMenu: $this->texteOptionnel($donnees, 'titre_menu'),
                idMenu: $this->texteOptionnel($donnees, 'id_menu'),
                introAccueil: $this->texteOptionnel($donnees, 'intro_accueil'),
            );
        }

        $pages = [];
        foreach ($this->depot->pages() as $slug => $page) {
            if (!isset($rubriques[$page->rubrique])) {
                throw new \RuntimeException(sprintf('La page « %s » utilise la rubrique inconnue « %s ».', $slug, $page->rubrique));
            }
            if ($page->estPubliee() || $avecBrouillons) {
                $pages[$slug] = $page;
            }
        }

        $menu = [];
        foreach ($this->section($config, 'menu') as $position => $entree) {
            $slug = $this->texteOptionnel($entree, 'page');
            if (null !== $slug) {
                $menu[] = new EntreeMenu(page: $pages[$slug] ?? throw new \RuntimeException(sprintf('Le menu cite la page « %s », inconnue ou non publiée.', $slug)));
                continue;
            }
            $id = $this->texteOptionnel($entree, 'rubrique')
                ?? throw new \RuntimeException(sprintf('L\'entrée de menu n°%s doit avoir une clé « page » ou « rubrique ».', $position));
            $rubrique = $rubriques[$id] ?? throw new \RuntimeException(sprintf('Le menu cite la rubrique inconnue « %s ».', $id));
            if (null === $rubrique->titreMenu || null === $rubrique->idMenu) {
                throw new \RuntimeException(sprintf('La rubrique « %s » est dans le menu : elle doit avoir « titre_menu » et « id_menu ».', $id));
            }
            $menu[] = new EntreeMenu(
                rubrique: $rubrique,
                pages: array_values(array_filter($pages, static fn (Page $page): bool => $page->rubrique === $id)),
            );
        }

        $site = $this->section($config, 'site');

        return new Site(
            nom: $this->texte($site, 'nom', 'site'),
            url: rtrim($this->texte($site, 'url', 'site'), '/'),
            annee: \is_int($site['annee'] ?? null) ? $site['annee'] : throw new \RuntimeException('La clé « site.annee » est obligatoire et doit être une année (nombre entier).'),
            rubriques: $rubriques,
            pages: $pages,
            menu: $menu,
            matomo: $this->matomo($site),
            modeleArticle: $this->modeleArticle($config),
        );
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    private function section(array $config, string $cle): array
    {
        $section = $config[$cle] ?? null;
        if (!\is_array($section)) {
            throw new \RuntimeException(sprintf('La section « %s » est absente de %s.', $cle, $this->fichierSite));
        }

        return $section;
    }

    private function texte(mixed $donnees, string $cle, string $contexte): string
    {
        return $this->texteOptionnel($donnees, $cle)
            ?? throw new \RuntimeException(sprintf('La clé « %s.%s » est obligatoire.', $contexte, $cle));
    }

    private function texteOptionnel(mixed $donnees, string $cle): ?string
    {
        $valeur = \is_array($donnees) ? ($donnees[$cle] ?? null) : null;

        return \is_scalar($valeur) && '' !== (string) $valeur ? (string) $valeur : null;
    }

    /**
     * Section facultative « site.matomo » : { url, site_id }.
     *
     * @param array<mixed> $site
     */
    private function matomo(array $site): ?Matomo
    {
        if (!isset($site['matomo'])) {
            return null;
        }
        if (!\is_array($site['matomo'])) {
            throw new \RuntimeException('La clé « site.matomo » doit contenir « url » et « site_id ».');
        }
        $siteId = $site['matomo']['site_id'] ?? null;
        if (!\is_int($siteId) && !(\is_string($siteId) && '' !== $siteId)) {
            throw new \RuntimeException('La clé « site.matomo.site_id » est obligatoire.');
        }

        return new Matomo($this->texte($site['matomo'], 'url', 'site.matomo'), (string) $siteId);
    }

    /**
     * Section facultative « nouvel_article » : { publication, url, traducteur }.
     *
     * @param array<mixed> $config
     */
    private function modeleArticle(array $config): ModeleArticle
    {
        $modele = $config['nouvel_article'] ?? [];
        if (!\is_array($modele)) {
            throw new \RuntimeException('La clé « nouvel_article » doit contenir « publication », « url » ou « traducteur ».');
        }

        return new ModeleArticle(
            publication: $this->texteOptionnel($modele, 'publication'),
            url: $this->texteOptionnel($modele, 'url'),
            traducteur: $this->texteOptionnel($modele, 'traducteur'),
        );
    }
}
