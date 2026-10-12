<?php

declare(strict_types=1);

namespace App\Controle;

/**
 * Couleurs autorisées sur le site et couples texte/fond dont le contraste a été validé.
 *
 * Ajouter une couleur dans accueil.css sans la déclarer ici fait échouer les tests :
 * il faut d'abord vérifier son contraste et l'inscrire dans PAIRES.
 */
final class Palette
{
    /** Contraste minimal d'un texte courant (RGAA 3.2). */
    public const float TEXTE = 4.5;
    /** Contraste minimal d'un grand texte (24 px, ou 18,5 px en gras) et d'un élément graphique (RGAA 3.3). */
    public const float GRAND_TEXTE = 3.0;

    /** Couleurs de accueil.css, sous leur forme canonique. */
    public const array COULEURS = [
        '#0051a8', '#ffffff', '#800080', '#f5f5f5', '#00ff7f', '#e7f1ff', '#c00000', '#1f2328', '#f6f8fa', '#0593ff',
        '#f5deb3', '#008000', '#000000', '#ffe0e0', '#dcf5e3', '#88603d', '#111d1d',
    ];

    /**
     * Couples validés : [usage, couleur du texte, couleur du fond, contraste minimal].
     *
     * @var list<array{string, string, string, float}>
     */
    public const array PAIRES = [
        ['Texte courant (Bootstrap) sur blanc', '#212529', '#ffffff', self::TEXTE],
        ['Liens sur blanc', '#0051a8', '#ffffff', self::TEXTE],
        ['Liens au survol sur blanc', '#800080', '#ffffff', self::TEXTE],
        ['Code en ligne (Bootstrap) sur blanc', '#d63384', '#ffffff', self::TEXTE],
        ['Titre et chapeau des articles sur blanc', '#c00000', '#ffffff', self::TEXTE],
        ['Rubriques de l\'accueil sur blanc (taille fixée à 24 px)', '#0593ff', '#ffffff', self::GRAND_TEXTE],
        ['Pied de page sur blanc', '#800080', '#ffffff', self::TEXTE],
        ['Menu déroulant et sommaire au survol', '#0051a8', '#e7f1ff', self::TEXTE],
        ['Menu déroulant et sommaire, page ou section courante', '#ffffff', '#0051a8', self::TEXTE],
        ['Légende d\'un listing', '#ffffff', '#0051a8', self::TEXTE],
        ['Code d\'un listing', '#1f2328', '#f6f8fa', self::TEXTE],
        ['Ligne supprimée d\'un diff', '#1f2328', '#ffe0e0', self::TEXTE],
        ['Ligne ajoutée d\'un diff', '#1f2328', '#dcf5e3', self::TEXTE],
        ['Légende d\'un tableau sur blanc', '#1f2328', '#ffffff', self::TEXTE],
        ['Diff de merge_request, ligne supprimée', '#88603d', '#ffffff', self::TEXTE],
        ['Diff de merge_request, ligne ajoutée', '#008000', '#ffffff', self::TEXTE],
        ['Mémo : texte sur fond sombre', '#f5f5f5', '#111d1d', self::TEXTE],
        ['Mémo : titres sur fond sombre', '#00ff7f', '#111d1d', self::TEXTE],
        ['Mémo : commentaires sur fond sombre', '#f5deb3', '#111d1d', self::TEXTE],
        ['Mémo : lien (souligné) sur fond sombre', '#0593ff', '#111d1d', self::TEXTE],
        ['Icône du menu mobile sur blanc', '#0051a8', '#ffffff', self::GRAND_TEXTE],
    ];
}
