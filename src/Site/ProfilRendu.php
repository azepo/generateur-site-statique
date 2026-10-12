<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Habillage du texte d'une page : classes CSS des titres et des blocs de code.
 *
 * Le Markdown dit ce qu'est un élément (un titre, du code) ; le profil dit à quoi il ressemble,
 * selon la famille de pages.
 */
enum ProfilRendu: string
{
    case Page = 'page';
    case Article = 'article';
    /** Notes de version (PHP 8.x, Symfony 6.x) : comme un article, avec des titres de section plus discrets. */
    case Version = 'version';
    case Memo = 'memo';

    /**
     * Classes CSS des titres, par niveau. Un titre peut toujours imposer les siennes : « ## Titre {.h4} ».
     *
     * @return array<int, string>
     */
    public function classesTitres(): array
    {
        return match ($this) {
            self::Page => [2 => 'h4 margin-top-25'],
            self::Article => [2 => 'h3', 3 => 'h4', 4 => 'h5'],
            self::Version => [2 => 'h4', 3 => 'h5 text-indent'],
            self::Memo => [2 => 'titre', 3 => 'sous-titre'],
        };
    }

    /** Classe CSS d'un bloc de code sans légende. */
    public function classeBlocCode(): ?string
    {
        return self::Memo === $this ? null : 'listing';
    }

    /** Commentaires des blocs de code PHP mis en couleur (classe « commentaire »), comme dans un éditeur. */
    public function commentairesColores(): bool
    {
        return self::Memo === $this;
    }

    /** Paragraphes justifiés avec alinéa (voir ParagrapheRenderer), comme dans les articles. */
    public function paragraphesEnAlineas(): bool
    {
        return self::Article === $this || self::Version === $this;
    }

    public static function parDefaut(TypePage $type): self
    {
        return TypePage::Article === $type ? self::Article : self::Page;
    }
}
