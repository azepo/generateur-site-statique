<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Reconnaît la syntaxe [texte]{lang=en}.
 */
final readonly class PassageLangueParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\[([^\[\]\n]+)\]\{lang=([A-Za-z]{2,3}(?:-[A-Za-z]{2,4})?)\}');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        [$texte, $langue] = $inlineContext->getSubMatches();
        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new PassageLangue($texte, $langue));

        return true;
    }
}
