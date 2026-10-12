<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le glossaire est lu par la commande Claude Code /traduire-article : il doit rester du YAML valide et cohérent.
 */
final class GlossaireTest extends TestCase
{
    public function testLeGlossaireEstValideEtCoherent(): void
    {
        $glossaire = Yaml::parseFile(\dirname(__DIR__).'/config/glossaire.yaml');

        self::assertIsArray($glossaire);
        self::assertSame(['conserver', 'traduire', 'intouchable', 'typographie'], array_keys($glossaire));
        foreach (['conserver', 'intouchable', 'typographie'] as $section) {
            self::assertIsList($glossaire[$section]);
            self::assertContainsOnlyString($glossaire[$section]);
            self::assertSame(array_unique($glossaire[$section]), $glossaire[$section], sprintf('Doublon dans « %s ».', $section));
        }
        self::assertIsArray($glossaire['traduire']);
        self::assertContainsOnlyString($glossaire['traduire']);

        // Un terme ne peut pas être à la fois gardé en anglais et traduit.
        self::assertSame([], array_values(array_intersect($glossaire['conserver'], array_keys($glossaire['traduire']))));
    }

    public function testLaCommandeDeTraductionRenvoieADesFichiersQuiExistent(): void
    {
        $projet = \dirname(__DIR__);
        $commande = (string) file_get_contents($projet.'/.claude/commands/traduire-article.md');

        preg_match_all('#`((?:context|config|content)/[^`$]+\.(?:md|yaml))`#', $commande, $trouves);
        self::assertNotEmpty($trouves[1]);
        foreach ($trouves[1] as $fichier) {
            self::assertFileExists($projet.'/'.$fichier);
        }
    }
}
