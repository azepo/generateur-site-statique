<?php

declare(strict_types=1);

namespace App\Tests\Generateur;

use App\Generateur\Assets;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class AssetsTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-assets-'.bin2hex(random_bytes(4));
        (new Filesystem())->dumpFile($this->dossier.'/src/site.css', 'body { color: black; }');
        (new Filesystem())->dumpFile($this->dossier.'/src/lib/outil.min.js', 'console.log(1);');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
    }

    public function testLeNomPublieContientUneEmpreinte(): void
    {
        $assets = new Assets($this->dossier.'/src', new Filesystem());

        self::assertMatchesRegularExpression('#^assets/site\.[0-9a-f]{8}\.css$#', $assets->chemin('site.css'));
        self::assertMatchesRegularExpression('#^assets/lib/outil\.min\.[0-9a-f]{8}\.js$#', $assets->chemin('lib/outil.min.js'));
    }

    public function testLEmpreinteChangeQuandLeContenuChange(): void
    {
        $avant = (new Assets($this->dossier.'/src', new Filesystem()))->chemin('site.css');
        (new Filesystem())->dumpFile($this->dossier.'/src/site.css', 'body { color: navy; }');
        $apres = (new Assets($this->dossier.'/src', new Filesystem()))->chemin('site.css');

        self::assertNotSame($avant, $apres);
    }

    public function testPublieLesFichiersSousLeurNomAvecEmpreinte(): void
    {
        $assets = new Assets($this->dossier.'/src', new Filesystem());
        $ecrits = $assets->publier($this->dossier.'/dist');

        self::assertCount(2, $ecrits);
        self::assertStringEqualsFile($this->dossier.'/dist/'.$assets->chemin('site.css'), 'body { color: black; }');
    }

    public function testRefuseUnFichierInconnu(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Assets($this->dossier.'/src', new Filesystem()))->chemin('absent.css');
    }
}
