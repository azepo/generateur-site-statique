<?php

declare(strict_types=1);

namespace App\Contenu;

/**
 * Un fichier de contenu a un en-tête absent, incomplet ou incohérent.
 */
final class ContenuInvalide extends \RuntimeException
{
    public static function dans(string $fichier, string $raison): self
    {
        return new self(sprintf('%s : %s', $fichier, $raison));
    }
}
