<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * Un passage écrit dans une autre langue que celle de la page : [Meetup in a Box]{lang=en}.
 */
final class PassageLangue extends AbstractInline
{
    public function __construct(
        public readonly string $texte,
        public readonly string $langue,
    ) {
        parent::__construct();
    }
}
