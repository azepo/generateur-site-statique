<?php

declare(strict_types=1);

namespace App\Tests\Contenu;

use App\Contenu\Markdown\MarkdownInvalide;
use App\Contenu\RenduMarkdown;
use App\Site\ProfilRendu;
use App\Site\StyleParagraphes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Critère d'acceptation de la phase 3 : chaque construction du Markdown enrichi
 * produit exactement le HTML défini par les conventions d'accessibilité du site.
 */
final class RenduMarkdownTest extends TestCase
{
    private const string HOTE = 'example.org';

    // --- Titres ---------------------------------------------------------------------------------

    public function testLesTitresRecoiventLesClassesDuProfil(): void
    {
        self::assertSame(
            "<h2 class=\"h4 margin-top-25\">Limites connues</h2>\n<h3>Détail</h3>\n",
            $this->html("## Limites connues\n\n### Détail\n", ProfilRendu::Page),
        );
        self::assertSame(
            "<h2 class=\"h3\">CQRS</h2>\n<h3 class=\"h4\">Stockage</h3>\n<h4 class=\"h5\">Le contrôleur</h4>\n",
            $this->html("## CQRS\n\n### Stockage\n\n#### Le contrôleur\n", ProfilRendu::Article),
        );
        self::assertSame(
            "<h2 class=\"titre\">Types &amp; Variables</h2>\n<h3 class=\"sous-titre\">Factory</h3>\n",
            $this->html("## Types & Variables\n\n### Factory\n", ProfilRendu::Memo),
        );
    }

    public function testUnTitrePeutImposerSaClasse(): void
    {
        self::assertSame("<h2 class=\"h4\">Titre</h2>\n", $this->html('## Titre {.h4}', ProfilRendu::Article));
    }

    public function testUnTitreDeNiveau1EstRefuse(): void
    {
        $this->expectException(MarkdownInvalide::class);
        $this->expectExceptionMessage('titre de niveau 1');
        $this->html("# Titre de la page\n");
    }

    public function testLesTitresAvecIdentifiantAlimententLeSommaire(): void
    {
        $rendu = (new RenduMarkdown())->rendre(<<<'MD'
            ## Types & Variables {#variables}

            ## Sans identifiant

            ## Late Static Binding (Résolution statique à la volée) {#late_static_binding sommaire="Late static binding"}

            ### Factory {#factory}
            MD, ProfilRendu::Memo, self::HOTE);

        self::assertSame(
            [[2, 'variables', 'Types & Variables'], [2, 'late_static_binding', 'Late static binding'], [3, 'factory', 'Factory']],
            array_map(static fn ($titre): array => [$titre->niveau, $titre->id, $titre->libelle], $rendu->titres),
        );
        self::assertCount(2, $rendu->titresDeNiveau(2));
        self::assertStringContainsString('<h2 id="late_static_binding" class="titre">Late Static Binding (Résolution statique à la volée)</h2>', $rendu->html);
        self::assertStringNotContainsString('sommaire=', $rendu->html, 'L\'attribut « sommaire » ne doit pas être écrit dans la page.');
    }

    public function testDeuxTitresNePeuventPasAvoirLeMemeIdentifiant(): void
    {
        $this->expectException(MarkdownInvalide::class);
        $this->expectExceptionMessage('« intro » est utilisé deux fois');
        $this->html("## A {#intro}\n\n## B {#intro}\n");
    }

    // --- Blocs de code --------------------------------------------------------------------------

    public function testBlocDeCodeSimple(): void
    {
        self::assertSame(
            "<pre class=\"listing\"><code>\$x = 1 &lt; 2 &amp;&amp; true;\necho \$a-&gt;b;</code></pre>\n",
            $this->html("```php\n\$x = 1 < 2 && true;\necho \$a->b;\n```\n"),
        );
    }

    public function testBlocDeCodeDuMemoSansClasse(): void
    {
        self::assertSame("<pre><code>unset(\$a);</code></pre>\n", $this->html("```php\nunset(\$a);\n```\n", ProfilRendu::Memo));
    }

    public function testListingAvecLegende(): void
    {
        self::assertSame(
            "<figure class=\"listing\">\n<figcaption>Listing 1.</figcaption>\n<pre><code>&lt;?php\n\nclass A\n{\n   private \$id;\n}</code></pre>\n</figure>\n",
            $this->html("```php title=\"Listing 1.\"\n<?php\n\nclass A\n{\n   private \$id;\n}\n```\n"),
        );
    }

    public function testLIndentationEtLesLignesVidesDuCodeSontConservees(): void
    {
        $html = $this->html("```\nif (\$a) {\n\n        return;\n}\n```\n");

        self::assertStringContainsString("if (\$a) {\n\n        return;\n}", $html);
    }

    public function testDiff(): void
    {
        self::assertSame(
            "<pre class=\"listing\"><code><span class=\"diff-suppr\">- function a(string \$p)  {</span>\n"
            ."<span class=\"diff-ajout\">+ function a(#[\\S] string \$p)  {</span>\n"
            ."       debug();</code></pre>\n",
            $this->html("```diff\n- function a(string \$p)  {\n+ function a(#[\\S] string \$p)  {\n       debug();\n```\n"),
        );
    }

    public function testDeuxListingsQuiSeSuiventSontCoteACote(): void
    {
        $html = $this->html("```php title=\"Listing 8.\"\nA\n```\n\n```php title=\"Listing 9.\"\nB\n```\n");

        self::assertSame(
            "<div class=\"row justify-content-center\">\n"
            ."<div class=\"col-auto mb-3\">\n<figure class=\"listing\">\n<figcaption>Listing 8.</figcaption>\n<pre><code>A</code></pre>\n</figure>\n</div>\n"
            ."<div class=\"col-auto mb-3\">\n<figure class=\"listing\">\n<figcaption>Listing 9.</figcaption>\n<pre><code>B</code></pre>\n</figure>\n</div>\n"
            ."</div>\n",
            $html,
        );
    }

    public function testDesListingsSeparesParDuTexteRestentSeulsEtLeTexteEstConserve(): void
    {
        $markdown = "```php title=\"Listing 1.\"\nA\n```\n\nTexte entre les deux.\n\n```php title=\"Listing 2.\"\nB\n```\n\n```php title=\"Listing 3.\"\nC\n```\n\nTexte final.\n";
        $html = $this->html($markdown);

        self::assertStringContainsString('Texte entre les deux.', $html);
        self::assertStringContainsString('Texte final.', $html);
        self::assertSame(3, substr_count($html, '<figure class="listing">'));
        self::assertSame(1, substr_count($html, '<div class="row justify-content-center">'), 'Seuls les listings 2 et 3 se suivent.');
        self::assertStringNotContainsString("row justify-content-center\">\n<div class=\"col-auto mb-3\">\n<figure class=\"listing\">\n<figcaption>Listing 1.", $html);
    }

    // --- Schémas --------------------------------------------------------------------------------

    public function testSchemaAvecDescription(): void
    {
        self::assertSame(
            "<figure class=\"schema\">\n<img src=\"./media/cqrs-images/figure1.jpg\" alt=\"Schéma : une base pour la lecture et l'écriture\">\n"
            ."<figcaption><details><summary>Description de la figure 1</summary><p>L'application cliente envoie la requête.</p>\n<p>Deuxième paragraphe.</p></details></figcaption>\n</figure>\n",
            $this->html(<<<'MD'
                ```schema src="./media/cqrs-images/figure1.jpg" alt="Schéma : une base pour la lecture et l'écriture" titre="figure 1"
                L'application cliente envoie la requête.

                Deuxième paragraphe.
                ```
                MD),
        );
    }

    public function testSchemaAvecTranscriptionDansUneAutreLangue(): void
    {
        $html = $this->html(<<<'MD'
            ```schema src="./media/adr.jpg" alt="Capture d'écran de l'ADR" resume="Transcription de la capture" lang="en"
            **Decision** : self-hosted.

            - When upstream providers require external embedding.
            ```
            MD);

        self::assertStringContainsString('<summary>Transcription de la capture</summary><div lang="en"><p><strong>Decision</strong> : self-hosted.</p>', $html);
        self::assertStringContainsString("<ul>\n<li>When upstream providers require external embedding.</li>\n</ul></div></details>", $html);
    }

    public function testCommentairesPhpColoresDansLeMemo(): void
    {
        $code = "```php\n\$url = 'http://exemple.test'; // une adresse\n/* bloc\nsur deux lignes */\n# dièse\n```\n";

        self::assertSame(
            "<pre><code>\$url = 'http://exemple.test'; <span class=\"commentaire\">// une adresse</span>\n"
            ."<span class=\"commentaire\">/* bloc\nsur deux lignes */</span>\n"
            ."<span class=\"commentaire\"># dièse</span></code></pre>\n",
            $this->html($code, ProfilRendu::Memo),
        );
        // Ailleurs que dans le mémo, le code reste du texte brut.
        self::assertStringNotContainsString('commentaire', $this->html($code));
        // Un bloc sans langage (résultat affiché, par exemple) n'est pas analysé.
        self::assertSame("<pre><code>// texte</code></pre>\n", $this->html("```\n// texte\n```\n", ProfilRendu::Memo));
    }

    public function testBaliseOuvrantePhpConserveeDansLeMemo(): void
    {
        self::assertSame(
            "<pre><code>&lt;?php <span class=\"commentaire\">// début</span></code></pre>\n",
            $this->html("```php\n<?php // début\n```\n", ProfilRendu::Memo),
        );
    }

    public function testListeSerreeSansParagrapheDansLesElements(): void
    {
        self::assertSame("<ul>\n<li>un</li>\n<li>deux</li>\n</ul>\n", $this->html("- un\n- deux\n"));
        self::assertSame("<ul>\n<li>\n<p>un</p>\n</li>\n<li>\n<p>deux</p>\n</li>\n</ul>\n", $this->html("- un\n\n- deux\n"));
    }

    public function testSchemaSimpleSansDescription(): void
    {
        self::assertSame(
            "<figure class=\"schema\">\n<img src=\"./media/figure4.jpg\" alt=\"Suite d'évènements : ItemAdded, OrderPaid\">\n</figure>\n",
            $this->html("```schema src=\"./media/figure4.jpg\" alt=\"Suite d'évènements : ItemAdded, OrderPaid\"\n```\n"),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function schemasInvalides(): iterable
    {
        yield 'sans image' => ["```schema alt=\"Un schéma\"\n```\n", 'doit indiquer son image'];
        yield 'sans texte alternatif' => ["```schema src=\"a.jpg\"\n```\n", 'doit avoir un texte alternatif'];
        yield 'texte alternatif vide' => ["```schema src=\"a.jpg\" alt=\" \"\n```\n", 'doit avoir un texte alternatif'];
    }

    #[DataProvider('schemasInvalides')]
    public function testUnSchemaSansImageOuSansTexteAlternatifEstRefuse(string $markdown, string $message): void
    {
        $this->expectException(MarkdownInvalide::class);
        $this->expectExceptionMessage($message);
        $this->html($markdown);
    }

    // --- Tableaux -------------------------------------------------------------------------------

    public function testTableauAvecLegendeEtEnTetesDeColonne(): void
    {
        self::assertSame(
            "<table class=\"table table-sm table-bordered w-auto donnees\">\n<caption>État de la base avant</caption>\n"
            ."<thead>\n<tr>\n<th scope=\"col\">id</th>\n<th scope=\"col\">name</th>\n</tr>\n</thead>\n"
            ."<tbody>\n<tr>\n<td>1</td>\n<td>Ada Exemple</td>\n</tr>\n</tbody>\n</table>\n",
            $this->html("{legende=\"État de la base avant\"}\n| id | name |\n|----|------|\n| 1  | Ada Exemple |\n"),
        );
    }

    public function testUnTableauSansLegendeEstRefuse(): void
    {
        $this->expectException(MarkdownInvalide::class);
        $this->expectExceptionMessage('Un tableau doit avoir une légende');
        $this->html("| id | name |\n|----|------|\n| 1  | Junior |\n");
    }

    // --- Langue et liens ------------------------------------------------------------------------

    public function testPassageDansUneAutreLangue(): void
    {
        self::assertSame(
            "<p>L'initiative « <span lang=\"en\">Meetup in a Box</span> » et <span lang=\"en-US\">A &amp; B</span>.</p>\n",
            $this->html("L'initiative « [Meetup in a Box]{lang=en} » et [A & B]{lang=en-US}.", ProfilRendu::Page),
        );
    }

    public function testLeHtmlEcritALaMainEstConserve(): void
    {
        self::assertStringContainsString(
            '<span lang="en">CQRS and Event Sourcing</span>',
            $this->html('Article « <span lang="en">CQRS and Event Sourcing</span> ».'),
        );
    }

    public function testUnLienExterneSOuvreDansUnNouvelOngletEtLAnnonce(): void
    {
        self::assertSame(
            '<p><a target="_blank" rel="noopener" aria-label="Manuel PHP – ouvre dans une nouvelle fenêtre (onglet)" href="https://www.php.net/manual/fr/"><em>Manuel PHP</em></a></p>'."\n",
            $this->html('[*Manuel PHP*](https://www.php.net/manual/fr/)', ProfilRendu::Page),
        );
    }

    public function testLeLibelleDUnLienExterneReprendUnTexteEnAnglais(): void
    {
        self::assertSame(
            '<p>Source : <a target="_blank" rel="noopener" aria-label="New in Symfony 6.1: draft emails – ouvre dans une nouvelle fenêtre (onglet)" href="https://symfony.com/blog/x"><span lang="en">New in Symfony 6.1: draft emails</span></a></p>'."\n",
            $this->html('Source : [[New in Symfony 6.1: draft emails]{lang=en}](https://symfony.com/blog/x)', ProfilRendu::Page),
        );
    }

    public function testUnLienInterneOuUneAncreResteInchange(): void
    {
        self::assertSame(
            '<p><a href="https://example.org/plan_du_site.html">plan</a>, <a href="#variables">ancre</a>, <a href="PHP81.html">relatif</a></p>'."\n",
            $this->html('[plan](https://example.org/plan_du_site.html), [ancre](#variables), [relatif](PHP81.html)', ProfilRendu::Page),
        );
    }

    public function testUnLienExterneSansTexteEstRefuse(): void
    {
        $this->expectException(MarkdownInvalide::class);
        $this->expectExceptionMessage('n\'a pas de texte');
        $this->html('[](https://www.php.net/)');
    }

    // --- Paragraphes des articles ---------------------------------------------------------------

    public function testUnParagrapheDArticleEstJustifieAvecUnAlinea(): void
    {
        self::assertSame(
            "<p class=\"word\"><span class=\"text-indent\"><strong>Les Fibers</strong> : un bloc de code.</span></p>\n",
            $this->html('**Les Fibers** : un bloc de code.', ProfilRendu::Version),
        );
    }

    public function testChaqueLigneDUnParagrapheDArticleDonneUnAlinea(): void
    {
        self::assertSame(
            "<p class=\"word\"><span class=\"text-indent linebreak\">Premier alinéa.</span>\n<span class=\"text-indent linebreak\">Deuxième, avec <code>du code</code>.</span></p>\n",
            $this->html("Premier alinéa.\nDeuxième, avec `du code`.", ProfilRendu::Article),
        );
    }

    public function testParagrapheJustifieSansAlineaEtParagrapheSimple(): void
    {
        self::assertSame("<p class=\"word\">Introduction.</p>\n", $this->html("Introduction.\n{.sans-alinea}", ProfilRendu::Article));
        self::assertSame("<p>Plus d'info : lien.</p>\n", $this->html("Plus d'info : lien.\n{.simple}", ProfilRendu::Article));
        self::assertSame("<p class=\"margin-top-25\">Mise à jour.</p>\n", $this->html("Mise à jour.\n{.margin-top-25}", ProfilRendu::Article));
    }

    public function testLesParagraphesDUneListeOuDUneDescriptionRestentOrdinaires(): void
    {
        $html = $this->html("- élément\n\n  suite de l'élément\n", ProfilRendu::Article);
        self::assertStringContainsString("<p>suite de l'élément</p>", $html);

        $html = $this->html("```schema src=\"a.jpg\" alt=\"Un schéma\" titre=\"figure 1\"\nDescription.\n```\n", ProfilRendu::Article);
        self::assertStringContainsString('<p>Description.</p>', $html);
    }

    public function testAvecLeStyleJustifiesLesParagraphesNOntPasDAlinea(): void
    {
        $rendu = (new RenduMarkdown())->rendre("Premier paragraphe.\n\nSource : lien.\n{.simple}", ProfilRendu::Version, self::HOTE, StyleParagraphes::Justifies);

        self::assertSame("<p class=\"word\">Premier paragraphe.</p>\n<p>Source : lien.</p>\n", $rendu->html);
    }

    public function testLesSousTitresDUneNoteDeVersionSontEnRetrait(): void
    {
        self::assertSame("<h2 class=\"h4\">Symfony 6.1</h2>\n<h3 class=\"h5 text-indent\">Nouveau composant</h3>\n", $this->html("## Symfony 6.1\n\n### Nouveau composant\n", ProfilRendu::Version));
    }

    public function testLesPagesSimplesNOntPasDAlinea(): void
    {
        self::assertSame("<p>Bonjour.</p>\n", $this->html('Bonjour.', ProfilRendu::Page));
        self::assertSame("<p>Tout. PHP est conçu pour le web.</p>\n", $this->html('Tout. PHP est conçu pour le web.', ProfilRendu::Memo));
    }

    public function testUnBlocPeutRecevoirUneClasse(): void
    {
        self::assertSame("<p class=\"margin-top-25\">Page mise à jour.</p>\n", $this->html("Page mise à jour.\n{.margin-top-25}\n", ProfilRendu::Page));
    }

    private function html(string $markdown, ProfilRendu $profil = ProfilRendu::Article): string
    {
        return (new RenduMarkdown())->rendre($markdown, $profil, self::HOTE)->html;
    }
}
