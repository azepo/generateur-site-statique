<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contenu\RenduMarkdown;
use App\Site\ProfilRendu;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Filtre Twig « markdown_ligne » : rend un court texte Markdown sans paragraphe autour.
 * Sert aux champs de l'en-tête qui portent une mise en forme, par exemple « La **Revue** d'exemple ».
 */
final class MarkdownExtension extends AbstractExtension
{
    public function __construct(private readonly RenduMarkdown $markdown)
    {
    }

    public function getFilters(): array
    {
        return [new TwigFilter('markdown_ligne', $this->ligne(...))];
    }

    private function ligne(string $texte): Markup
    {
        $html = trim($this->markdown->rendre($texte, ProfilRendu::Page, '')->html);

        return new Markup((string) preg_replace('#^<p>(.*)</p>$#s', '$1', $html), 'UTF-8');
    }
}
