<?php

declare(strict_types=1);

namespace App\Tests\Controle;

use App\Controle\Palette;
use Azepo\RgaaCheck\Contrast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Les contrastes de la palette sont calculés une fois pour toutes ici.
 */
final class PaletteTest extends TestCase
{
    public function testLeCalculDeContrasteSuitLaFormuleWcag(): void
    {
        self::assertEqualsWithDelta(21.0, Contrast::ratio('black', 'white'), 0.001);
        self::assertEqualsWithDelta(1.0, Contrast::ratio('#fff', '#ffffff'), 0.001);
        self::assertEqualsWithDelta(7.66, Contrast::ratio('#0051A8', 'white'), 0.01);
        self::assertEqualsWithDelta(6.48, Contrast::ratio('#C00000', 'white'), 0.01);
        self::assertSame('#111d1d', Contrast::hex('rgb(17, 29, 29)'));
    }

    /**
     * @return iterable<string, array{string, string, float}>
     */
    public static function paires(): iterable
    {
        foreach (Palette::PAIRES as [$usage, $texte, $fond, $minimum]) {
            yield $usage => [$texte, $fond, $minimum];
        }
    }

    #[DataProvider('paires')]
    public function testChaqueCoupleValideAUnContrasteSuffisant(string $texte, string $fond, float $minimum): void
    {
        self::assertGreaterThanOrEqual($minimum, round(Contrast::ratio($texte, $fond), 2));
    }

    public function testToutesLesCouleursDeLaFeuilleDeStyleSontDansLaPalette(): void
    {
        $css = (string) file_get_contents(\dirname(__DIR__, 2).'/assets/accueil.css');
        preg_match_all('/#[0-9a-f]{3,6}\b|rgb\([^)]*\)|(?<=[\s:,])(?:white|black|purple|green|red|whitesmoke|springgreen|wheat|blue|gray|grey|orange|yellow)(?=[\s;!,])/i', $css, $trouvees);
        $couleurs = array_unique(array_map(Contrast::hex(...), $trouvees[0]));
        sort($couleurs);

        self::assertNotEmpty($couleurs);
        self::assertSame([], array_values(array_diff($couleurs, Palette::COULEURS)), 'Couleur absente de la palette : vérifier son contraste, puis l\'ajouter à App\Controle\Palette.');
    }

    public function testUneCouleurInconnueEstRefusee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Contrast::hex('bleu canard');
    }
}
