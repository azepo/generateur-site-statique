<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Une page du site : ses métadonnées (en-tête YAML) et son texte (Markdown).
 */
final readonly class Page
{
    /**
     * @param array<string, mixed> $donnees autres clés de l'en-tête, propres à un gabarit (ex. « accroche » pour l'accueil)
     */
    public function __construct(
        public string $slug,
        public string $titre,
        public string $rubrique,
        public TypePage $type = TypePage::Page,
        public Statut $statut = Statut::Publie,
        public int $ordre = 0,
        public ?string $titreMenu = null,
        public ?string $titreLang = null,
        public ?string $titrePage = null,
        public ?string $titreH1 = null,
        public ?string $titreH1Lang = null,
        public string $description = '',
        public string $motsCles = '',
        public ?string $resume = null,
        public ?string $chapeau = null,
        public ?\DateTimeImmutable $modifie = null,
        public bool $dansPlan = true,
        public ?ProfilRendu $profil = null,
        public StyleParagraphes $paragraphes = StyleParagraphes::Alineas,
        public string $corps = '',
        public ?Source $source = null,
        public ?Traduction $traduction = null,
        public array $donnees = [],
    ) {
    }

    /** Libellé affiché dans le menu : le titre court s'il existe, sinon le titre. */
    public function libelleMenu(): string
    {
        return $this->titreMenu ?? $this->titre;
    }

    /** Titre affiché en tête de page : « titre_h1 » s'il existe, sinon le titre. */
    public function titrePrincipal(): string
    {
        return $this->titreH1 ?? $this->titre;
    }

    /** Langue du titre principal, s'il n'est pas en français. */
    public function langueDuTitrePrincipal(): ?string
    {
        return null !== $this->titreH1 ? $this->titreH1Lang : $this->titreLang;
    }

    public function estAccueil(): bool
    {
        return TypePage::Accueil === $this->type;
    }

    public function estPubliee(): bool
    {
        return Statut::Publie === $this->statut;
    }

    /** Habillage du texte : celui de l'en-tête (« profil »), sinon celui du type de page. */
    public function profilRendu(): ProfilRendu
    {
        return $this->profil ?? ProfilRendu::parDefaut($this->type);
    }

    /** Nom du fichier généré, identique aux URL actuelles du site. */
    public function fichier(): string
    {
        return $this->slug.'.html';
    }

    /** Valeur d'une clé de l'en-tête propre à un gabarit. */
    public function donnee(string $cle): ?string
    {
        $valeur = $this->donnees[$cle] ?? null;

        return \is_scalar($valeur) ? (string) $valeur : null;
    }
}
