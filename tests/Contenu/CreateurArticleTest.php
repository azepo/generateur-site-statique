<?php

declare(strict_types=1);

namespace App\Tests\Contenu;

use App\Contenu\CreateurArticle;
use App\Contenu\DepotContenu;
use App\Site\ChargeurSite;
use App\Site\Statut;
use App\Site\TypePage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class CreateurArticleTest extends TestCase
{
    private string $dossier;
    private CreateurArticle $createur;
    private ChargeurSite $chargeur;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-article-'.bin2hex(random_bytes(4));
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/site.yaml', <<<'YAML'
            site: { nom: "Site", url: "https://exemple.test", annee: 2026 }
            rubriques:
                general: { titre_plan: "Général" }
                articles: { titre_plan: "Articles", titre_menu: "Articles", id_menu: articlesMenu }
            menu:
                - { rubrique: articles }
            nouvel_article:
                publication: "Revue Exemple"
                url: "https://revue.example.org/"
                traducteur: "Jon"
            YAML);
        $fichiers->dumpFile($this->dossier.'/content/pages/index.md', "---\ntitre: Accueil\ntype: accueil\nrubrique: general\n---\n");
        $fichiers->dumpFile($this->dossier.'/content/articles/Ancien/index.fr.md', "---\ntitre: Ancien\ntype: article\nrubrique: articles\nordre: 40\n---\n");

        $this->chargeur = new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content'));
        $this->createur = new CreateurArticle($this->chargeur, $fichiers, $this->dossier.'/content');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
    }

    public function testCreeUnBrouillonLisibleParLeGenerateur(): void
    {
        $fichier = $this->creer('Mon_Article', 'Un "bon" titre');

        self::assertSame($this->dossier.'/content/articles/Mon_Article/index.fr.md', $fichier);
        self::assertDirectoryExists($this->dossier.'/content/articles/Mon_Article/media');

        // Un brouillon n'est pas publié…
        self::assertArrayNotHasKey('Mon_Article', $this->chargeur->charger()->pages);
        // … mais il se charge sans erreur pour la prévisualisation.
        $page = $this->chargeur->charger(avecBrouillons: true)->page('Mon_Article');
        self::assertSame('Un "bon" titre', $page->titre);
        self::assertSame(TypePage::Article, $page->type);
        self::assertSame(Statut::Brouillon, $page->statut);
        self::assertSame('articles', $page->rubrique);
        self::assertSame(50, $page->ordre, 'Le nouvel article se place après le dernier de sa rubrique.');
        self::assertSame('Revue Exemple', $page->source?->publication);
        self::assertSame('Jon', $page->traduction?->traducteur);
        self::assertStringContainsString('modifie: 2026-10-05', (string) file_get_contents($fichier));
    }

    public function testSansValeursParDefautLesSectionsSourceEtTraductionSontEnCommentaire(): void
    {
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/site.yaml', (string) preg_replace('/nouvel_article:.*\z/s', '', (string) file_get_contents($this->dossier.'/site.yaml')));

        $fichier = $this->creer('Sans_Defaut', 'Titre');
        $contenu = (string) file_get_contents($fichier);

        self::assertStringContainsString("# source:\n#     publication: \"\"", $contenu);
        self::assertStringContainsString("# traduction:\n#     traducteur: \"\"", $contenu);
        // L'article reste lisible par le générateur : les sections en commentaire sont ignorées.
        $page = $this->chargeur->charger(avecBrouillons: true)->page('Sans_Defaut');
        self::assertNull($page->source);
        self::assertNull($page->traduction);
    }

    public function testSourceWeb(): void
    {
        $this->creer('Page_Web', 'Titre', url: 'https://blog.example.org/2026/article');

        $source = $this->chargeur->charger(avecBrouillons: true)->page('Page_Web')->source;
        self::assertNotNull($source);
        self::assertSame('blog.example.org', $source->publication);
        self::assertSame('https://blog.example.org/2026/article', $source->url);
    }

    public function testCopieLePdf(): void
    {
        file_put_contents($this->dossier.'/magazine.pdf', '%PDF-1.7');

        $this->creer('Avec_Pdf', 'Titre', pdf: $this->dossier.'/magazine.pdf');

        self::assertStringEqualsFile($this->dossier.'/content/articles/Avec_Pdf/source.pdf', '%PDF-1.7');
        // Le PDF est ignoré par le générateur.
        self::assertCount(3, $this->chargeur->charger(avecBrouillons: true)->pages);
    }

    public function testRefuseUnSlugInvalide(): void
    {
        $this->expectExceptionMessage('Slug « mon article » invalide');
        $this->creer('mon article', 'Titre');
    }

    public function testRefuseUnSlugDejaUtilise(): void
    {
        $this->expectExceptionMessage('Le slug « Ancien » est déjà utilisé.');
        $this->creer('Ancien', 'Titre');
    }

    public function testRefuseLeSlugDUnePageSimple(): void
    {
        $this->expectExceptionMessage('Le slug « index » est déjà utilisé.');
        $this->creer('index', 'Titre');
    }

    public function testRefuseUneRubriqueInconnue(): void
    {
        $this->expectExceptionMessage('Rubrique « blog » inconnue. Rubriques du site : general, articles.');
        $this->creer('Nouveau', 'Titre', rubrique: 'blog');
    }

    public function testRefuseUnPdfIntrouvable(): void
    {
        try {
            $this->creer('Nouveau', 'Titre', pdf: $this->dossier.'/absent.pdf');
            self::fail('Un PDF introuvable doit être refusé.');
        } catch (\InvalidArgumentException $erreur) {
            self::assertStringContainsString('PDF introuvable', $erreur->getMessage());
        }
        // Rien n'est créé quand la demande est refusée.
        self::assertDirectoryDoesNotExist($this->dossier.'/content/articles/Nouveau');
    }

    public function testRefuseUneAdresseInvalide(): void
    {
        $this->expectExceptionMessage('Adresse de la source invalide');
        $this->creer('Nouveau', 'Titre', url: 'blog.example.org/article');
    }

    private function creer(string $slug, string $titre, string $rubrique = 'articles', ?string $url = null, ?string $pdf = null): string
    {
        return $this->createur->creer($slug, $titre, $rubrique, new \DateTimeImmutable('2026-10-05'), $url, $pdf);
    }
}
