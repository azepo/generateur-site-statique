<?php

declare(strict_types=1);

namespace App\Tests\Contenu;

use App\Contenu\ContenuInvalide;
use App\Contenu\DepotContenu;
use App\Site\Statut;
use App\Site\TypePage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DepotContenuTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/generateur-contenu-'.bin2hex(random_bytes(4));
        (new Filesystem())->mkdir([$this->dossier.'/pages', $this->dossier.'/articles']);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
    }

    public function testLitUnePageEtUnArticle(): void
    {
        $this->ecrire('pages/contact.md', "---\ntitre: Contact\nrubrique: general\nordre: 20\n---\n\nBonjour.\n");
        $this->ecrire('articles/Mon_Article/index.fr.md', <<<'MD'
            ---
            titre: "My Article"
            titre_lang: en
            type: article
            rubrique: articles
            ordre: 10
            statut: brouillon
            modifie: 2026-10-01
            accroche: "Clé libre"
            source:
                publication: "Revue Exemple"
                auteur: "Jane Doe"
                volume: 25
            traduction:
                traducteur: "Jon"
            ---

            ## Section
            MD);

        $pages = (new DepotContenu($this->dossier))->pages();

        self::assertSame(['Mon_Article', 'contact'], array_keys($pages), 'Tri par ordre, puis par slug.');

        $article = $pages['Mon_Article'];
        self::assertSame(TypePage::Article, $article->type);
        self::assertSame(Statut::Brouillon, $article->statut);
        self::assertFalse($article->estPubliee());
        self::assertSame('en', $article->titreLang);
        self::assertSame('2026-10-01', $article->modifie?->format('Y-m-d'));
        self::assertSame('## Section', $article->corps);
        self::assertNotNull($article->source);
        self::assertSame('Jane Doe', $article->source->auteur);
        self::assertSame(25, $article->source->volume);
        self::assertNotNull($article->traduction);
        self::assertSame('Jon', $article->traduction->traducteur);
        self::assertSame('Clé libre', $article->donnee('accroche'));
        self::assertSame('Mon_Article.html', $article->fichier());
        self::assertSame('My Article', $article->titrePrincipal(), 'Sans « titre_h1 », le titre principal est le titre.');

        $page = $pages['contact'];
        self::assertSame(TypePage::Page, $page->type, 'Type par défaut.');
        self::assertTrue($page->estPubliee(), 'Statut par défaut : publié.');
        self::assertSame('Bonjour.', $page->corps);
    }

    public function testIgnoreLesAutresFichiers(): void
    {
        $this->ecrire('pages/contact.md', "---\ntitre: Contact\nrubrique: general\n---\n");
        $this->ecrire('articles/Mon_Article/source.en.md', '# Original text, no front matter');
        $this->ecrire('articles/Mon_Article/notes.txt', 'notes');

        self::assertSame(['contact'], array_keys((new DepotContenu($this->dossier))->pages()));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function contenusInvalides(): iterable
    {
        yield 'sans en-tête' => ["Bonjour\n", 'en-tête YAML absent'];
        yield 'en-tête illisible' => ["---\ntitre: [oups\n---\n", 'en-tête YAML illisible'];
        yield 'titre manquant' => ["---\nrubrique: general\n---\n", 'la clé « titre » est obligatoire'];
        yield 'rubrique manquante' => ["---\ntitre: T\n---\n", 'la clé « rubrique » est obligatoire'];
        yield 'type inconnu' => ["---\ntitre: T\nrubrique: general\ntype: billet\n---\n", 'type « billet » inconnu'];
        yield 'statut inconnu' => ["---\ntitre: T\nrubrique: general\nstatut: fini\n---\n", 'statut « fini » inconnu'];
        yield 'slug invalide' => ["---\ntitre: T\nrubrique: general\nslug: 'mon article'\n---\n", 'slug « mon article » invalide'];
        yield 'date invalide' => ["---\ntitre: T\nrubrique: general\nmodifie: hier\n---\n", 'la clé « modifie » doit être une date'];
        yield 'style de paragraphes inconnu' => ["---\ntitre: T\nrubrique: general\nparagraphes: centres\n---\n", 'valeur « centres » inconnue pour « paragraphes »'];
        yield 'profil inconnu' => ["---\ntitre: T\nrubrique: general\nprofil: journal\n---\n", 'profil « journal » inconnu'];
        yield 'ordre non entier' => ["---\ntitre: T\nrubrique: general\nordre: premier\n---\n", 'la clé « ordre » doit être un nombre entier'];
        yield 'volume non entier' => ["---\ntitre: T\nrubrique: general\nsource: { publication: P, volume: vingt }\n---\n", 'la clé « source.volume » doit être un nombre entier'];
        yield 'source sans publication' => ["---\ntitre: T\nrubrique: general\nsource: { auteur: A }\n---\n", '« source.publication » est obligatoire'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('contenusInvalides')]
    public function testRefuseUnContenuInvalide(string $contenu, string $message): void
    {
        $this->ecrire('pages/page.md', $contenu);

        $this->expectException(ContenuInvalide::class);
        $this->expectExceptionMessage($message);
        (new DepotContenu($this->dossier))->pages();
    }

    public function testRefuseDeuxContenusAvecLeMemeSlug(): void
    {
        $this->ecrire('pages/doublon.md', "---\ntitre: A\nrubrique: general\n---\n");
        $this->ecrire('articles/doublon/index.fr.md', "---\ntitre: B\nrubrique: articles\n---\n");

        $this->expectException(ContenuInvalide::class);
        $this->expectExceptionMessage('le slug « doublon » est déjà utilisé');
        (new DepotContenu($this->dossier))->pages();
    }

    private function ecrire(string $chemin, string $contenu): void
    {
        (new Filesystem())->dumpFile($this->dossier.'/'.$chemin, $contenu);
    }
}
