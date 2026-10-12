<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Rend une cellule de tableau. Une cellule d'en-tête annonce sa colonne : <th scope="col">.
 */
final readonly class CelluleRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof TableCell) {
            throw new \InvalidArgumentException(sprintf('Nœud inattendu : %s.', $node::class));
        }
        $contenu = $childRenderer->renderNodes($node->children());

        return TableCell::TYPE_HEADER === $node->getType()
            ? '<th scope="col">'.$contenu.'</th>'
            : '<td>'.$contenu.'</td>';
    }
}
