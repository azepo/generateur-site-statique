<?php

declare(strict_types=1);

namespace App\Site;

/**
 * Nature d'une page : détermine le gabarit utilisé pour la générer.
 */
enum TypePage: string
{
    case Accueil = 'accueil';
    case Plan = 'plan';
    case Page = 'page';
    case Article = 'article';
}
