<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

/**
 * Le texte Markdown ne respecte pas une règle du site (souvent une règle d'accessibilité).
 */
final class MarkdownInvalide extends \RuntimeException
{
}
