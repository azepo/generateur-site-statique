<?php

declare(strict_types=1);

namespace App\Tests\Generateur;

use App\Generateur\Medias;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class MediasTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-medias-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
    }

    public function testLesImagesDesArticlesSontPublieesDansMedia(): void
    {
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/content/articles/CQRS/media/cqrs-images/figure1.jpg', 'A');
        $fichiers->dumpFile($this->dossier.'/content/articles/PHP82/media/php82/dnf.jpg', 'B');
        $fichiers->dumpFile($this->dossier.'/content/articles/CQRS/source.pdf', 'pdf');
        $fichiers->dumpFile($this->dossier.'/content/articles/CQRS/index.fr.md', '---');

        $ecrits = (new Medias($this->dossier.'/content', $fichiers))->publier($this->dossier.'/dist');

        self::assertSame([$this->dossier.'/dist/media/cqrs-images/figure1.jpg', $this->dossier.'/dist/media/php82/dnf.jpg'], $ecrits);
        self::assertStringEqualsFile($this->dossier.'/dist/media/cqrs-images/figure1.jpg', 'A');
        self::assertFileDoesNotExist($this->dossier.'/dist/media/source.pdf');
    }

    public function testDeuxImagesDifferentesNePeuventPasAvoirLeMemeChemin(): void
    {
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/content/articles/A/media/figure1.jpg', 'A');
        $fichiers->dumpFile($this->dossier.'/content/articles/B/media/figure1.jpg', 'B');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('portent le même nom « media/figure1.jpg »');
        (new Medias($this->dossier.'/content', $fichiers))->publier($this->dossier.'/dist');
    }

    public function testSansImageRienNEstPublie(): void
    {
        (new Filesystem())->dumpFile($this->dossier.'/content/pages/index.md', '---');

        self::assertSame([], (new Medias($this->dossier.'/content', new Filesystem()))->publier($this->dossier.'/dist'));
    }
}
