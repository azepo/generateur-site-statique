<?php

declare(strict_types=1);

namespace App\Site;

/**
 * État de publication d'un contenu. Seul un contenu publié est généré et listé.
 */
enum Statut: string
{
    case Brouillon = 'brouillon';
    case ARelire = 'a-relire';
    case Publie = 'publie';
}
