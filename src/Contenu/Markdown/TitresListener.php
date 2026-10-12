<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use App\Contenu\Titre;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Relève les titres qui portent un identifiant, pour construire le sommaire de la page.
 *
 *   ## Types & Variables {#variables}
 *   ## Late Static Binding (Résolution statique à la volée) {#late_static_binding sommaire="Late static binding"}
 *
 * L'attribut « sommaire » donne un libellé plus court pour le sommaire ; il n'est pas écrit dans la page.
 */
final readonly class TitresListener
{
    public const string CLE = 'titres';

    public function __invoke(DocumentParsedEvent $evenement): void
    {
        $document = $evenement->getDocument();
        $titres = [];
        $ids = [];

        foreach ($document->iterator() as $noeud) {
            if (!$noeud instanceof Heading) {
                continue;
            }
            if (1 === $noeud->getLevel()) {
                throw new MarkdownInvalide('Le texte ne doit pas contenir de titre de niveau 1 (« # ») : le titre de la page vient de son en-tête. Commencer à « ## ».');
            }

            $libelle = $noeud->data->get('attributes/sommaire', null);
            if ($noeud->data->has('attributes/sommaire')) {
                $attributs = (array) $noeud->data->get('attributes');
                unset($attributs['sommaire']);
                $noeud->data->set('attributes', $attributs);
            }

            $id = $noeud->data->get('attributes/id', null);
            if (!\is_string($id) || '' === $id) {
                continue;
            }
            if (isset($ids[$id])) {
                throw new MarkdownInvalide(sprintf('L\'identifiant de titre « %s » est utilisé deux fois.', $id));
            }
            $ids[$id] = true;

            $titres[] = new Titre(
                $noeud->getLevel(),
                $id,
                \is_string($libelle) && '' !== $libelle ? $libelle : trim(StringContainerHelper::getChildText($noeud)),
            );
        }

        $document->data->set(self::CLE, $titres);
    }
}
