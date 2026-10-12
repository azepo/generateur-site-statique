<?php

declare(strict_types=1);

namespace App\Contenu;

use App\Site\ChargeurSite;
use App\Site\ModeleArticle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Prépare le dossier d'un nouvel article à traduire :
 *
 *   content/articles/<slug>/index.fr.md   en-tête YAML à compléter, « statut: brouillon »
 *   content/articles/<slug>/media/        images de l'article
 *   content/articles/<slug>/source.pdf    copie du PDF d'origine, s'il est fourni
 *
 * L'article reste un brouillon : il n'est ni généré ni listé tant que son statut n'est pas « publie ».
 */
final readonly class CreateurArticle
{
    public function __construct(
        private ChargeurSite $chargeur,
        private Filesystem $fichiers,
        private string $dossierContenu,
    ) {
    }

    /**
     * @param string      $slug      adresse de la page : <slug>.html
     * @param string|null $urlSource adresse de la page web à traduire (source web)
     * @param string|null $pdf       chemin du PDF à traduire (source magazine)
     *
     * @return string chemin du fichier index.fr.md créé
     */
    public function creer(
        string $slug,
        string $titre,
        string $rubrique,
        \DateTimeImmutable $jour,
        ?string $urlSource = null,
        ?string $pdf = null,
    ): string {
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            throw new \InvalidArgumentException(sprintf('Slug « %s » invalide : lettres, chiffres, « _ » et « - » uniquement.', $slug));
        }
        if ('' === trim($titre)) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        // Brouillons compris : deux contenus ne peuvent pas porter la même adresse.
        $site = $this->chargeur->charger(avecBrouillons: true);
        if (!isset($site->rubriques[$rubrique])) {
            throw new \InvalidArgumentException(sprintf('Rubrique « %s » inconnue. Rubriques du site : %s.', $rubrique, implode(', ', array_keys($site->rubriques))));
        }
        $dossier = $this->dossierContenu.'/articles/'.$slug;
        if (isset($site->pages[$slug]) || $this->fichiers->exists($dossier)) {
            throw new \InvalidArgumentException(sprintf('Le slug « %s » est déjà utilisé.', $slug));
        }
        if (null !== $urlSource && 1 !== preg_match('#^https?://[^\s/]+#', $urlSource)) {
            throw new \InvalidArgumentException(sprintf('Adresse de la source invalide : « %s ».', $urlSource));
        }
        if (null !== $pdf && !is_file($pdf)) {
            throw new \InvalidArgumentException(sprintf('PDF introuvable : « %s ».', $pdf));
        }

        // Le nouvel article se place à la fin de sa rubrique.
        $ordre = 0;
        foreach ($site->pages as $page) {
            if ($page->rubrique === $rubrique) {
                $ordre = max($ordre, $page->ordre);
            }
        }

        $this->fichiers->mkdir($dossier.'/media');
        if (null !== $pdf) {
            $this->fichiers->copy($pdf, $dossier.'/source.pdf');
        }
        $fichier = $dossier.'/index.fr.md';
        $this->fichiers->dumpFile($fichier, $this->entete($titre, $rubrique, $ordre + 10, $jour, $urlSource, $site->modeleArticle));

        return $fichier;
    }

    private function entete(string $titre, string $rubrique, int $ordre, \DateTimeImmutable $jour, ?string $urlSource, ModeleArticle $modele): string
    {
        // Source : la page web traduite, sinon la publication par défaut du site (section « nouvel_article »).
        $publication = null === $urlSource ? $modele->publication : (string) parse_url($urlSource, \PHP_URL_HOST);
        $url = $urlSource ?? $modele->url;
        $numero = null === $urlSource ? "    # volume: 1\n    # numero: 1\n    # date: \"01/2026\"\n" : "    # date: \"01/2026\"\n";
        // Sans publication connue, la section reste en commentaire : elle est alors ignorée à la lecture.
        $source = null === $publication
            ? "# source:\n#     publication: \"\"\n#     url: \"\"\n#     auteur: \"\"\n"
            : "source:\n    publication: {$this->chaine($publication)}\n".(null === $url ? '' : "    url: {$this->chaine($url)}\n")."    # auteur: \"\"\n".$numero;
        $traduction = null === $modele->traducteur
            ? "# traduction:\n#     traducteur: \"\"\n"
            : "traduction:\n    traducteur: {$this->chaine($modele->traducteur)}\n";

        return "---\n"
            ."titre: {$this->chaine($titre)}\n"
            ."# titre_lang: en          # si le titre reste en anglais\n"
            ."# titre_menu: \"\"          # libellé plus court pour le menu\n"
            ."type: article\n"
            ."rubrique: {$rubrique}\n"
            ."ordre: {$ordre}\n"
            ."statut: brouillon\n"
            ."modifie: {$jour->format('Y-m-d')}\n"
            ."description: \"\"\n"
            ."mots_cles: \"\"\n"
            ."resume: \"\"\n"
            ."chapeau: \"\"\n"
            .$source
            .$traduction
            ."---\n\n";
    }

    /** Valeur YAML entre guillemets doubles. */
    private function chaine(string $texte): string
    {
        return '"'.addcslashes(trim($texte), '"\\').'"';
    }
}
