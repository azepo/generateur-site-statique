<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Présentation des paragraphes d'un article ou d'une note de version (clé « paragraphes » de l'en-tête).
 */
enum StyleParagraphes: string
{
    /** Justifiés, avec un alinéa par ligne du fichier : présentation des articles de magazine. */
    case Alineas = 'alineas';
    /** Justifiés, sans alinéa, séparés par un espace : présentation des notes de version Symfony. */
    case Justifies = 'justifies';
}
