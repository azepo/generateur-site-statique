<?php

declare(strict_types=1);

namespace App\Tests\Site;

use App\Contenu\DepotContenu;
use App\Site\ChargeurSite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ChargeurSiteTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-site-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
    }

    public function testMatomoEtModeleDArticleSontFacultatifs(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\n---\n");
        $site = (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();

        self::assertNull($site->matomo);
        self::assertNull($site->modeleArticle->publication);
        self::assertNull($site->modeleArticle->traducteur);
    }

    public function testMatomoEtModeleDArticleSontLus(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\n---\n");
        file_put_contents($this->dossier.'/site.yaml', <<<'YAML'
            site:
                nom: Test
                url: 'https://exemple.test'
                annee: 2026
                matomo: { url: "//stats.exemple.test/", site_id: 7 }
            rubriques: { general: { titre_plan: Général } }
            menu: []
            nouvel_article: { publication: "Mon magazine", traducteur: "Ada" }
            YAML);

        $site = (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();

        self::assertNotNull($site->matomo);
        self::assertSame('//stats.exemple.test/', $site->matomo->url);
        self::assertSame('7', $site->matomo->siteId);
        self::assertSame('Mon magazine', $site->modeleArticle->publication);
        self::assertNull($site->modeleArticle->url);
        self::assertSame('Ada', $site->modeleArticle->traducteur);
    }

    public function testUnBrouillonNEstPasPublie(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\n---\n", "---\ntitre: Brouillon\nrubrique: general\nstatut: brouillon\n---\n");
        $chargeur = new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content'));

        self::assertSame(['publiee'], array_keys($chargeur->charger()->pages));
        self::assertSame(['brouillon', 'publiee'], array_keys($chargeur->charger(avecBrouillons: true)->pages));
    }

    public function testRefuseUneRubriqueInconnue(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: inconnue\n---\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('rubrique inconnue « inconnue »');
        (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();
    }

    public function testRefuseUnMenuQuiCiteUnePageNonPubliee(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\nstatut: brouillon\n---\n", null, 'menu: [{ page: publiee }]');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le menu cite la page « publiee », inconnue ou non publiée.');
        (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();
    }

    public function testRefuseUneEntreeDeMenuSansPageNiRubrique(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\n---\n", null, 'menu: [{ lien: ailleurs }]');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('doit avoir une clé « page » ou « rubrique »');
        (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();
    }

    public function testRefuseUneAnneeQuiNEstPasUnNombre(): void
    {
        $this->site("---\ntitre: Publiée\nrubrique: general\n---\n");
        (new Filesystem())->dumpFile($this->dossier.'/site.yaml', "site: { nom: Test, url: 'https://exemple.test', annee: 'deux mille' }\nrubriques: { general: { titre_plan: Général } }\nmenu: []\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('doit être une année');
        (new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')))->charger();
    }

    private function site(string $publiee, ?string $brouillon = null, string $menu = 'menu: []'): void
    {
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/site.yaml', "site: { nom: Test, url: 'https://exemple.test', annee: 2026 }\nrubriques: { general: { titre_plan: Général } }\n$menu\n");
        $fichiers->dumpFile($this->dossier.'/content/pages/publiee.md', $publiee);
        if (null !== $brouillon) {
            $fichiers->dumpFile($this->dossier.'/content/pages/brouillon.md', $brouillon);
        }
    }
}
