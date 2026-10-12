<?php

declare(strict_types=1);

namespace App\Contenu;

use App\Contenu\Markdown\BlocCodeRenderer;
use App\Contenu\Markdown\CelluleRenderer;
use App\Contenu\Markdown\LiensExternesListener;
use App\Contenu\Markdown\ParagrapheRenderer;
use App\Contenu\Markdown\PassageLangue;
use App\Contenu\Markdown\PassageLangueParser;
use App\Contenu\Markdown\PassageLangueRenderer;
use App\Contenu\Markdown\TableauRenderer;
use App\Contenu\Markdown\TitresListener;
use App\Site\ProfilRendu;
use App\Site\StyleParagraphes;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;

/**
 * Transforme le texte Markdown d'une page en HTML conforme aux conventions du site.
 *
 * En plus du Markdown standard :
 *   - listing avec légende, diff, schéma avec description (voir BlocCodeRenderer) ;
 *   - tableau avec légende obligatoire (voir TableauRenderer) ;
 *   - passage dans une autre langue : [texte]{lang=en} ;
 *   - liens externes ouverts dans un nouvel onglet et annoncés comme tels ;
 *   - attributs sur un bloc ou un titre : {.classe #identifiant}.
 *
 * Le titre de la page (<h1>) vient de l'en-tête YAML : dans le texte, les titres commencent à « ## ».
 */
final class RenduMarkdown
{
    /** @var array<string, MarkdownConverter> */
    private array $convertisseurs = [];

    /**
     * @param string $hoteSite nom de domaine du site : les liens vers un autre domaine sont externes
     */
    public function rendre(string $markdown, ProfilRendu $profil, string $hoteSite, StyleParagraphes $paragraphes = StyleParagraphes::Alineas): Rendu
    {
        $resultat = $this->convertisseur($profil, $hoteSite, $paragraphes)->convert($markdown);
        $titres = $resultat->getDocument()->data->get(TitresListener::CLE, []);
        $html = $profil->paragraphesEnAlineas() ? $this->listingsCoteACote($resultat->getContent()) : $resultat->getContent();

        return new Rendu($html, \is_array($titres) ? array_values(array_filter($titres, static fn (mixed $t): bool => $t instanceof Titre)) : []);
    }

    /**
     * Deux listings avec légende qui se suivent sont placés côte à côte sur grand écran
     * (et l'un sous l'autre quand la place manque), comme dans les articles du magazine.
     */
    private function listingsCoteACote(string $html): string
    {
        // (?:(?!</figure>).)* : le contenu d'UNE figure, sans jamais franchir sa balise fermante.
        $listing = '<figure class="listing">(?:(?!</figure>).)*</figure>';

        return (string) preg_replace_callback(
            '#'.$listing.'(?:\n'.$listing.')+#s',
            static function (array $groupe) use ($listing): string {
                preg_match_all('#'.$listing.'#s', $groupe[0], $figures);
                $colonnes = array_map(static fn (string $figure): string => "<div class=\"col-auto mb-3\">\n".$figure."\n</div>", $figures[0]);

                return "<div class=\"row justify-content-center\">\n".implode("\n", $colonnes)."\n</div>";
            },
            $html,
        );
    }

    private function convertisseur(ProfilRendu $profil, string $hoteSite, StyleParagraphes $paragraphes = StyleParagraphes::Alineas): MarkdownConverter
    {
        $cle = $profil->value.'|'.$hoteSite.'|'.$paragraphes->value;
        if (isset($this->convertisseurs[$cle])) {
            return $this->convertisseurs[$cle];
        }

        $classesTitres = $profil->classesTitres();
        $environnement = new Environment([
            // Le HTML est permis dans le Markdown : le contenu est écrit par l'auteur du site.
            'html_input' => 'allow',
            'default_attributes' => [
                Heading::class => [
                    // Classe du profil, sauf si le titre impose la sienne : « ## Titre {.h4} ».
                    'class' => static fn (Heading $titre): ?string => $titre->data->has('attributes/class')
                        ? null
                        : $classesTitres[$titre->getLevel()] ?? null,
                ],
            ],
        ]);
        $environnement->addExtension(new CommonMarkCoreExtension());
        $environnement->addExtension(new AttributesExtension());
        $environnement->addExtension(new DefaultAttributesExtension());
        $environnement->addExtension(new TableExtension());

        $environnement->addInlineParser(new PassageLangueParser(), 100);
        $environnement->addRenderer(PassageLangue::class, new PassageLangueRenderer());
        $environnement->addRenderer(Table::class, new TableauRenderer(), 10);
        $environnement->addRenderer(TableCell::class, new CelluleRenderer(), 10);
        $environnement->addRenderer(FencedCode::class, new BlocCodeRenderer(
            $profil->classeBlocCode(),
            // La description d'un schéma est elle-même du Markdown, rendu comme un texte ordinaire.
            fn (string $texte): string => $this->convertisseur(ProfilRendu::Page, $hoteSite)->convert($texte)->getContent(),
            $profil->commentairesColores(),
        ), 10);
        if ($profil->paragraphesEnAlineas()) {
            $environnement->addRenderer(Paragraph::class, new ParagrapheRenderer(StyleParagraphes::Alineas === $paragraphes), 10);
        }

        // Priorité négative : après l'application des attributs {…} écrits dans le texte.
        $environnement->addEventListener(DocumentParsedEvent::class, new LiensExternesListener($hoteSite), -10);
        $environnement->addEventListener(DocumentParsedEvent::class, new TitresListener(), -10);

        return $this->convertisseurs[$cle] = new MarkdownConverter($environnement);
    }
}
