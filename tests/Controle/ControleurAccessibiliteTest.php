<?php

declare(strict_types=1);

namespace App\Tests\Controle;

use App\Controle\ControleurAccessibilite;
use Azepo\RgaaCheck\Issue;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class ControleurAccessibiliteTest extends KernelTestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-controle-'.bin2hex(random_bytes(4));
        (new Filesystem())->mkdir($this->dossier);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
        parent::tearDown();
    }

    public function testUnDossierDePagesConformesEstConforme(): void
    {
        $this->ecrire('a.html', self::page('<p>A</p>', 'Page A'));
        $this->ecrire('b.html', self::page('<p>B</p>', 'Page B'));
        // Ne sont pas des pages du site : fichier de vérification Google, sous-dossier, autre type de fichier.
        $this->ecrire('google0123abcd.html', 'google-site-verification');
        $this->ecrire('assets/fragment.html', '<p>fragment</p>');
        $this->ecrire('robots.txt', 'User-agent: *');

        $rapport = $this->controleur()->controler($this->dossier);

        self::assertSame(['a.html', 'b.html'], $rapport->files);
        self::assertSame([], $rapport->issues);
        self::assertTrue($rapport->passes());
    }

    public function testDeuxPagesNePeuventPasAvoirLeMemeTitre(): void
    {
        $this->ecrire('a.html', self::page('<p>A</p>', 'Symfony 6.3'));
        $this->ecrire('b.html', self::page('<p>B</p>', 'Symfony 6.3'));

        $rapport = $this->controleur()->controler($this->dossier);

        self::assertFalse($rapport->passes());
        self::assertSame(
            ['a.html : titre de page « Symfony 6.3 » partagé avec b.html', 'b.html : titre de page « Symfony 6.3 » partagé avec a.html'],
            array_map(static fn (Issue $a): string => $a->file.' : '.$a->message, $rapport->errors()),
        );
    }

    public function testUnAvertissementNeRendPasLeSiteNonConforme(): void
    {
        $this->ecrire('a.html', self::page('<p>If you run this command as follows, the result is in the terminal.</p>'));

        $rapport = $this->controleur()->controler($this->dossier);

        self::assertTrue($rapport->passes());
        self::assertCount(1, $rapport->warnings());
    }

    public function testLaCommandeEchoueSurUnePageFautiveEtNommeLeProbleme(): void
    {
        $this->ecrire('fautive.html', self::page('<img src="a.jpg"><h3>Niveau sauté</h3>'));

        $commande = new CommandTester((new Application(self::bootKernel()))->find('site:check'));
        $code = $commande->execute(['dossier' => $this->dossier]);

        self::assertSame(Command::FAILURE, $code);
        self::assertStringContainsString('image sans attribut alt', $commande->getDisplay());
        self::assertStringContainsString('niveau de titre sauté', $commande->getDisplay());
        self::assertStringContainsString('2 erreur(s)', $commande->getDisplay());
    }

    public function testLaCommandeReussitSurUnDossierConforme(): void
    {
        $this->ecrire('a.html', self::page());

        $commande = new CommandTester((new Application(self::bootKernel()))->find('site:check'));

        self::assertSame(Command::SUCCESS, $commande->execute(['dossier' => $this->dossier]));
        self::assertStringContainsString('1 page(s) contrôlée(s), 0 erreur(s)', $commande->getDisplay());
    }

    public function testUnDossierAbsentEstRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->controleur()->controler($this->dossier.'/absent');
    }

    /** Une page conforme, que les tests abîment. */
    private static function page(string $contenu = '<p>Bonjour.</p>', string $titre = 'Page de test – exemple.test'): string
    {
        return <<<HTML
            <!doctype html>
            <html lang="fr">
            <head><title>$titre</title></head>
            <body>
              <a href="#contenu" class="visually-hidden-focusable">Aller au contenu</a>
              <header><nav><a href="https://exemple.test/">Accueil</a></nav></header>
              <main id="contenu" tabindex="-1">
                <h1>Titre de la page</h1>
                $contenu
              </main>
              <footer><p>2026</p></footer>
            </body>
            </html>
            HTML;
    }

    private function controleur(): ControleurAccessibilite
    {
        self::bootKernel();
        $controleur = static::getContainer()->get(ControleurAccessibilite::class);
        self::assertInstanceOf(ControleurAccessibilite::class, $controleur);

        return $controleur;
    }

    private function ecrire(string $fichier, string $contenu): void
    {
        (new Filesystem())->dumpFile($this->dossier.'/'.$fichier, $contenu);
    }
}
