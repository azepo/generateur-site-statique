<?php

declare(strict_types=1);

namespace App\Contenu;

use App\Site\Page;
use App\Site\ProfilRendu;
use App\Site\Source;
use App\Site\Statut;
use App\Site\StyleParagraphes;
use App\Site\Traduction;
use App\Site\TypePage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Lit les fichiers de contenu (Markdown + en-tête YAML) et en fait des pages.
 *
 * Deux emplacements sont reconnus :
 *   - content/pages/<slug>.md
 *   - content/articles/<slug>/index.fr.md
 */
final readonly class DepotContenu
{
    /** Clés de l'en-tête reconnues par le modèle. Les autres sont transmises telles quelles au gabarit. */
    private const array CLES_CONNUES = [
        'titre', 'titre_menu', 'titre_lang', 'titre_page', 'titre_h1', 'titre_h1_lang', 'slug', 'type', 'rubrique', 'ordre', 'statut',
        'description', 'mots_cles', 'resume', 'chapeau', 'modifie', 'plan', 'profil', 'paragraphes', 'source', 'traduction',
    ];

    public function __construct(private string $dossierContenu)
    {
    }

    /**
     * @return array<string, Page> pages indexées par slug, triées par ordre puis par slug
     */
    public function pages(): array
    {
        $fichiers = (new Finder())->files()
            ->in($this->dossierContenu)
            ->path('/^pages\/[^\/]+\.md$/')
            ->path('/^articles\/[^\/]+\/index\.fr\.md$/')
            ->sortByName();

        $pages = [];
        $origines = [];
        foreach ($fichiers as $fichier) {
            $relatif = $fichier->getRelativePathname();
            $page = $this->lire($relatif, $fichier->getContents());
            if (isset($pages[$page->slug])) {
                throw ContenuInvalide::dans($relatif, sprintf('le slug « %s » est déjà utilisé par %s.', $page->slug, $origines[$page->slug]));
            }
            $pages[$page->slug] = $page;
            $origines[$page->slug] = $relatif;
        }

        uasort($pages, static fn (Page $a, Page $b): int => [$a->ordre, $a->slug] <=> [$b->ordre, $b->slug]);

        return $pages;
    }

    private function lire(string $fichier, string $contenu): Page
    {
        $contenu = str_replace("\r\n", "\n", $contenu);
        if (1 !== preg_match('/\A---\n(.*?)\n---\n?(.*)\z/s', $contenu, $parties)) {
            throw ContenuInvalide::dans($fichier, 'en-tête YAML absent (le fichier doit commencer par « --- »).');
        }

        try {
            $entete = Yaml::parse($parties[1], Yaml::PARSE_DATETIME);
        } catch (ParseException $erreur) {
            throw ContenuInvalide::dans($fichier, 'en-tête YAML illisible : '.$erreur->getMessage());
        }
        if (!\is_array($entete)) {
            throw ContenuInvalide::dans($fichier, 'en-tête YAML vide.');
        }

        $slugParDefaut = str_ends_with($fichier, '/index.fr.md')
            ? basename(\dirname($fichier))
            : basename($fichier, '.md');
        $slug = $this->texte($entete, 'slug') ?? $slugParDefaut;
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            throw ContenuInvalide::dans($fichier, sprintf('slug « %s » invalide : lettres, chiffres, « _ » et « - » uniquement.', $slug));
        }

        $type = TypePage::tryFrom($this->texte($entete, 'type') ?? TypePage::Page->value)
            ?? throw ContenuInvalide::dans($fichier, sprintf('type « %s » inconnu (valeurs possibles : %s).', $this->texte($entete, 'type'), $this->valeurs(TypePage::cases())));
        $statut = Statut::tryFrom($this->texte($entete, 'statut') ?? Statut::Publie->value)
            ?? throw ContenuInvalide::dans($fichier, sprintf('statut « %s » inconnu (valeurs possibles : %s).', $this->texte($entete, 'statut'), $this->valeurs(Statut::cases())));

        $profil = null;
        if (null !== $this->texte($entete, 'profil')) {
            $profil = ProfilRendu::tryFrom((string) $this->texte($entete, 'profil'))
                ?? throw ContenuInvalide::dans($fichier, sprintf('profil « %s » inconnu (valeurs possibles : %s).', $this->texte($entete, 'profil'), $this->valeurs(ProfilRendu::cases())));
        }

        $paragraphes = StyleParagraphes::tryFrom($this->texte($entete, 'paragraphes') ?? StyleParagraphes::Alineas->value)
            ?? throw ContenuInvalide::dans($fichier, sprintf('valeur « %s » inconnue pour « paragraphes » (valeurs possibles : %s).', $this->texte($entete, 'paragraphes'), $this->valeurs(StyleParagraphes::cases())));

        $modifie = $entete['modifie'] ?? null;
        if (null !== $modifie && !$modifie instanceof \DateTimeInterface) {
            throw ContenuInvalide::dans($fichier, 'la clé « modifie » doit être une date au format AAAA-MM-JJ.');
        }

        return new Page(
            slug: $slug,
            titre: $this->texte($entete, 'titre') ?? throw ContenuInvalide::dans($fichier, 'la clé « titre » est obligatoire.'),
            rubrique: $this->texte($entete, 'rubrique') ?? throw ContenuInvalide::dans($fichier, 'la clé « rubrique » est obligatoire.'),
            type: $type,
            statut: $statut,
            ordre: $this->entier($fichier, $entete, 'ordre') ?? 0,
            titreMenu: $this->texte($entete, 'titre_menu'),
            titreLang: $this->texte($entete, 'titre_lang'),
            titrePage: $this->texte($entete, 'titre_page'),
            titreH1: $this->texte($entete, 'titre_h1'),
            titreH1Lang: $this->texte($entete, 'titre_h1_lang'),
            description: $this->texte($entete, 'description') ?? '',
            motsCles: $this->texte($entete, 'mots_cles') ?? '',
            resume: $this->texte($entete, 'resume'),
            chapeau: $this->texte($entete, 'chapeau'),
            modifie: null === $modifie ? null : \DateTimeImmutable::createFromInterface($modifie),
            dansPlan: (bool) ($entete['plan'] ?? true),
            profil: $profil,
            paragraphes: $paragraphes,
            corps: trim($parties[2]),
            source: $this->source($fichier, $entete['source'] ?? null),
            traduction: $this->traduction($fichier, $entete['traduction'] ?? null),
            donnees: $this->donneesLibres($entete),
        );
    }

    private function source(string $fichier, mixed $donnees): ?Source
    {
        if (null === $donnees) {
            return null;
        }
        if (!\is_array($donnees)) {
            throw ContenuInvalide::dans($fichier, 'la clé « source » doit contenir des sous-clés (publication, auteur…).');
        }

        return new Source(
            publication: $this->texte($donnees, 'publication') ?? throw ContenuInvalide::dans($fichier, 'la clé « source.publication » est obligatoire.'),
            url: $this->texte($donnees, 'url'),
            auteur: $this->texte($donnees, 'auteur'),
            volume: $this->entier($fichier, $donnees, 'volume', 'source.volume'),
            numero: $this->entier($fichier, $donnees, 'numero', 'source.numero'),
            date: $this->texte($donnees, 'date'),
            episode: $this->entier($fichier, $donnees, 'episode', 'source.episode'),
            auteurNote: $this->texte($donnees, 'auteur_note'),
        );
    }

    private function traduction(string $fichier, mixed $donnees): ?Traduction
    {
        if (null === $donnees) {
            return null;
        }
        if (!\is_array($donnees)) {
            throw ContenuInvalide::dans($fichier, 'la clé « traduction » doit contenir des sous-clés (traducteur, relecteur).');
        }

        return new Traduction($this->texte($donnees, 'traducteur'), $this->texte($donnees, 'relecteur'), $this->texte($donnees, 'relecteur_url'));
    }

    /**
     * @param array<mixed> $donnees
     */
    private function texte(array $donnees, string $cle): ?string
    {
        $valeur = $donnees[$cle] ?? null;

        return \is_scalar($valeur) && '' !== trim((string) $valeur) ? trim((string) $valeur) : null;
    }

    /**
     * @param array<mixed> $donnees
     */
    private function entier(string $fichier, array $donnees, string $cle, ?string $nom = null): ?int
    {
        $valeur = $donnees[$cle] ?? null;
        if (null === $valeur) {
            return null;
        }
        if (!\is_int($valeur)) {
            throw ContenuInvalide::dans($fichier, sprintf('la clé « %s » doit être un nombre entier.', $nom ?? $cle));
        }

        return $valeur;
    }

    /**
     * Clés de l'en-tête que le modèle ne connaît pas : elles sont transmises au gabarit.
     *
     * @param array<mixed> $entete
     *
     * @return array<string, mixed>
     */
    private function donneesLibres(array $entete): array
    {
        $donnees = [];
        foreach ($entete as $cle => $valeur) {
            if (\is_string($cle) && !\in_array($cle, self::CLES_CONNUES, true)) {
                $donnees[$cle] = $valeur;
            }
        }

        return $donnees;
    }

    /**
     * @param list<\BackedEnum> $cas
     */
    private function valeurs(array $cas): string
    {
        return implode(', ', array_map(static fn (\BackedEnum $c): string => (string) $c->value, $cas));
    }
}
