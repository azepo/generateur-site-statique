<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\StringContainerInterface;

/**
 * Les liens vers un autre site s'ouvrent dans un nouvel onglet, et l'annoncent :
 * target="_blank", rel="noopener" et aria-label="<texte visible> – ouvre dans une nouvelle fenêtre (onglet)".
 */
final readonly class LiensExternesListener
{
    public const string SUFFIXE = ' – ouvre dans une nouvelle fenêtre (onglet)';

    public function __construct(private string $hoteSite)
    {
    }

    public function __invoke(DocumentParsedEvent $evenement): void
    {
        foreach ($evenement->getDocument()->iterator() as $noeud) {
            if (!$noeud instanceof Link || !$this->estExterne($noeud->getUrl())) {
                continue;
            }

            $texte = trim((string) preg_replace('/\s+/u', ' ', $this->texte($noeud)));
            if ('' === $texte) {
                throw new MarkdownInvalide(sprintf('Le lien vers « %s » n\'a pas de texte.', $noeud->getUrl()));
            }

            $noeud->data->set('attributes/target', '_blank');
            $noeud->data->set('attributes/rel', 'noopener');
            if (!$noeud->data->has('attributes/aria-label')) {
                $noeud->data->set('attributes/aria-label', $texte.self::SUFFIXE);
            }
        }
    }

    /** Texte visible d'un lien, y compris celui d'un passage [texte]{lang=en}. */
    private function texte(Node $noeud): string
    {
        $texte = '';
        foreach ($noeud->iterator() as $descendant) {
            if ($descendant instanceof PassageLangue) {
                $texte .= $descendant->texte;
            } elseif ($descendant instanceof StringContainerInterface) {
                $texte .= $descendant->getLiteral();
            }
        }

        return $texte;
    }

    private function estExterne(string $url): bool
    {
        $hote = parse_url($url, \PHP_URL_HOST);

        return \is_string($hote) && '' !== $hote && strtolower($hote) !== strtolower($this->hoteSite);
    }
}
