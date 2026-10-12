<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;

final readonly class PassageLangueRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof PassageLangue) {
            throw new \InvalidArgumentException(sprintf('Nœud inattendu : %s.', $node::class));
        }

        return sprintf('<span lang="%s">%s</span>', Xml::escape($node->langue), Xml::escape($node->texte));
    }
}
