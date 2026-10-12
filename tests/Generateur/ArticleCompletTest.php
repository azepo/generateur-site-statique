<?php

declare(strict_types=1);

namespace App\Tests\Generateur;

use App\Contenu\DepotContenu;
use App\Contenu\RenduMarkdown;
use App\Generateur\Assets;
use App\Generateur\GenerateurSite;
use App\Generateur\Medias;
use App\Site\ChargeurSite;
use App\Site\ProfilRendu;
use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Un article qui utilise toutes les constructions du Markdown enrichi est généré de bout en bout,
 * avec les vrais gabarits du site.
 */
final class ArticleCompletTest extends KernelTestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-article-'.bin2hex(random_bytes(4));
        $fichiers = new Filesystem();
        $fichiers->dumpFile($this->dossier.'/site.yaml', <<<'YAML'
            site: { nom: "@test / site", url: "https://exemple.test", annee: 2026 }
            rubriques:
                general: { titre_plan: "Général" }
                articles: { titre_plan: "Articles", titre_menu: "Articles", id_menu: articlesMenu }
            menu:
                - { rubrique: articles }
            YAML);
        foreach (['index' => 'accueil', 'plan_du_site' => 'plan', 'accessibilite' => 'page'] as $slug => $type) {
            $fichiers->dumpFile($this->dossier."/content/pages/$slug.md", "---\ntitre: $slug\ntype: $type\nrubrique: general\n---\n");
        }
        $fichiers->dumpFile($this->dossier.'/content/articles/Article_Test/index.fr.md', <<<'MD'
            ---
            titre: "Test Article"
            titre_lang: en
            type: article
            rubrique: articles
            description: "Article de test"
            chapeau: "Un chapeau d'introduction."
            source:
                publication: "Revue Exemple"
                url: "https://revue.example.org/"
            ---

            ## Première section {#premiere}

            Un paragraphe avec [un terme anglais]{lang=en} et [un lien externe](https://www.php.net/).

            ```php title="Listing 1."
            <?php echo 1 < 2;
            ```

            ```diff
            - avant
            + après
            ```

            ### Sous-section {#sous-section}

            ```schema src="./media/figure.jpg" alt="Schéma : deux bases synchronisées" titre="figure 1"
            Le client écrit dans une base et lit dans l'autre.
            ```

            {legende="Contenu de la base"}
            | id | name |
            |----|------|
            | 1  | Ada  |
            MD);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
        parent::tearDown();
    }

    public function testLArticleEstGenereAvecToutesSesConstructions(): void
    {
        $html = $this->generer();

        self::assertStringContainsString('<title>Test Article – exemple.test</title>', $html);
        self::assertStringContainsString('<h1 class="h2 title" id="titre"><span lang="en">Test Article</span>', $html);
        self::assertStringContainsString('<p class="chapeau word">Un chapeau d&#039;introduction.</p>', $html);
        self::assertStringContainsString('<h2 id="premiere" class="h3">Première section</h2>', $html);
        self::assertStringContainsString('<span lang="en">un terme anglais</span>', $html);
        self::assertStringContainsString('aria-label="un lien externe – ouvre dans une nouvelle fenêtre (onglet)"', $html);
        self::assertStringContainsString("<figure class=\"listing\">\n<figcaption>Listing 1.</figcaption>\n<pre><code>&lt;?php echo 1 &lt; 2;</code></pre>", $html);
        self::assertStringContainsString('<span class="diff-suppr">- avant</span>', $html);
        self::assertStringContainsString('<summary>Description de la figure 1</summary>', $html);
        self::assertStringContainsString('<caption>Contenu de la base</caption>', $html);
        self::assertStringContainsString('<th scope="col">id</th>', $html);
        // L'article courant est signalé dans le menu.
        self::assertStringContainsString('<a class="dropdown-item active" aria-current="page" href="https://exemple.test/Article_Test.html">', $html);
    }

    public function testLeHtmlGenereNAAucuneErreurDAnalyseEtUnSeulTitrePrincipal(): void
    {
        $html = $this->generer();
        $analyseur = new HTML5();
        $document = $analyseur->loadHTML($html);

        self::assertSame([], $analyseur->getErrors());
        self::assertCount(1, $document->getElementsByTagName('h1'));
        self::assertCount(1, $document->getElementsByTagName('main'));
    }

    public function testLeSommaireEstConstruitAPartirDesTitres(): void
    {
        self::bootKernel();
        $twig = static::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);
        $rendu = (new RenduMarkdown())->rendre("## Types & Variables {#variables}\n\n## Référence {#reference}\n\n### Détail {#detail}\n", ProfilRendu::Memo, 'exemple.test');

        $html = $twig->render('_sommaire.html.twig', [
            'titres' => $rendu->titresDeNiveau(2),
            'id' => 'sommaire-memo',
            'libelle' => 'Sommaire du mémo',
            'classes' => 'col-12 col-lg-auto memo-sommaire',
            'premier' => ['id' => 'introduction', 'libelle' => 'Introduction'],
        ]);

        self::assertStringContainsString('<nav id="sommaire-memo" class="col-12 col-lg-auto memo-sommaire" aria-label="Sommaire du mémo">', $html);
        self::assertSame(3, substr_count($html, '<li>'), 'Introduction + les deux titres de niveau 2.');
        self::assertStringContainsString('<li><a class="nav-link" href="#introduction">Introduction</a></li>', $html);
        self::assertStringContainsString('<li><a class="nav-link" href="#variables">Types &amp; Variables</a></li>', $html);
        self::assertStringNotContainsString('#detail', $html);
    }

    private function generer(): string
    {
        $projet = self::bootKernel()->getProjectDir();
        $twig = static::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $generateur = new GenerateurSite(
            new ChargeurSite($this->dossier.'/site.yaml', new DepotContenu($this->dossier.'/content')),
            new RenduMarkdown(),
            new Assets($projet.'/assets', new Filesystem()),
            new Medias($this->dossier.'/content', new Filesystem()),
            $twig,
            new Filesystem(),
            $this->dossier.'/dist',
        );
        $generateur->generer();
        self::assertFileExists($this->dossier.'/dist/Article_Test.html');

        return (string) file_get_contents($this->dossier.'/dist/Article_Test.html');
    }
}
