<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;

/**
 * Rend un tableau de données avec sa légende (<caption>), obligatoire pour l'accessibilité.
 *
 * La légende se déclare sur la ligne qui précède le tableau : {legende="État de la base"}
 */
final readonly class TableauRenderer implements NodeRendererInterface
{
    private const string CLASSES = 'table table-sm table-bordered w-auto donnees';

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Table) {
            throw new \InvalidArgumentException(sprintf('Nœud inattendu : %s.', $node::class));
        }

        $legende = $node->data->get('attributes/legende', null);
        if (!\is_string($legende) || '' === trim($legende)) {
            throw new MarkdownInvalide('Un tableau doit avoir une légende : ajouter {legende="…"} sur la ligne qui le précède.');
        }

        return sprintf(
            "<table class=\"%s\">\n<caption>%s</caption>\n%s\n</table>",
            self::CLASSES,
            Xml::escape($legende),
            $childRenderer->renderNodes($node->children()),
        );
    }
}
