<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Block\TightBlockInterface;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Paragraphes des articles : justifiés, avec un alinéa en début de paragraphe.
 *
 * Un paragraphe écrit sur plusieurs lignes donne un alinéa par ligne, sans espace entre eux
 * (présentation des articles de magazine). Une ligne vide sépare deux groupes d'alinéas.
 *
 *   Texte du paragraphe.                  -> <p class="word"><span class="text-indent">…</span></p>
 *   Texte.{.sans-alinea} (ligne suivante) -> <p class="word">…</p>     justifié, sans retrait
 *   Texte.{.simple}      (ligne suivante) -> <p>…</p>                  paragraphe ordinaire
 *
 * Seuls les paragraphes du texte principal sont concernés, pas ceux d'une liste ou d'une citation.
 */
final readonly class ParagrapheRenderer implements NodeRendererInterface
{
    /**
     * @param bool $alineas faux : paragraphes justifiés sans alinéa (« paragraphes: justifies » dans l'en-tête)
     */
    public function __construct(private bool $alineas = true)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement|string
    {
        if (!$node instanceof Paragraph) {
            throw new \InvalidArgumentException(sprintf('Nœud inattendu : %s.', $node::class));
        }

        // Liste sans ligne vide entre ses éléments : le texte est écrit directement dans le <li>.
        $element = $node->parent();
        $liste = $element instanceof ListItem ? $element->parent() : null;
        if ($liste instanceof TightBlockInterface && $liste->isTight()) {
            return $childRenderer->renderNodes($node->children());
        }

        /** @var array<string, string> $attributs */
        $attributs = (array) $node->data->get('attributes', []);
        $classes = array_values(array_filter(explode(' ', $attributs['class'] ?? '')));
        $dansLeTexte = $node->parent() instanceof Document;

        if (!$dansLeTexte || [] !== array_diff($classes, ['sans-alinea']) || [] !== array_diff_key($attributs, ['class' => true])) {
            // Paragraphe ordinaire, ou habillage imposé par l'auteur. « simple » n'est qu'un mot-clé : il n'est pas écrit.
            $classes = array_values(array_diff($classes, ['simple']));
            unset($attributs['class']);
            if ([] !== $classes) {
                $attributs['class'] = implode(' ', $classes);
            }

            return new HtmlElement('p', $attributs, $childRenderer->renderNodes($node->children()));
        }

        if (!$this->alineas || \in_array('sans-alinea', $classes, true)) {
            return new HtmlElement('p', ['class' => 'word'], $childRenderer->renderNodes($node->children()));
        }

        // Un alinéa par ligne du fichier.
        $alineas = [[]];
        foreach ($node->children() as $enfant) {
            if ($enfant instanceof Newline && Newline::SOFTBREAK === $enfant->getType()) {
                $alineas[] = [];
                continue;
            }
            $alineas[array_key_last($alineas)][] = $enfant;
        }

        $classe = 1 === \count($alineas) ? 'text-indent' : 'text-indent linebreak';
        // Un retour à la ligne sépare les alinéas : sans lui, un lecteur d'écran enchaînerait
        // le dernier mot d'un alinéa et le premier du suivant.
        $spans = [];
        foreach ($alineas as $enfants) {
            $spans[] = (string) new HtmlElement('span', ['class' => $classe], $childRenderer->renderNodes($enfants));
        }

        return new HtmlElement('p', ['class' => 'word'], implode("\n", $spans));
    }
}
