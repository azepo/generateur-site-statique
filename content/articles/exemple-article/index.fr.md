---
titre: "Un article d'exemple"
type: article
rubrique: articles
ordre: 10
modifie: 2026-10-12
description: "Article d'exemple montrant la syntaxe des contenus du générateur."
mots_cles: "exemple, Markdown, accessibilité"
resume: "Tout ce qu'un article peut contenir, en une page."
chapeau: "Cet article ne parle de rien en particulier : il montre, sur une seule page, ce que le générateur sait produire à partir d'un fichier Markdown et de son en-tête."
# La signature (« Volume 1 - Numéro 2 (10/2026) par … / Traduit par … ») vient de ces deux sections.
# Ici, la publication et les personnes sont fictives.
source:
    publication: "La Revue d'exemple"
    url: "https://example.org/"
    auteur: "Ada Exemple"
    volume: 1
    numero: 2
    date: "10/2026"
traduction:
    traducteur: "Alex Exemple"
---

Un article est un fichier `content/articles/<slug>/index.fr.md`. Son en-tête donne le titre, la rubrique, la signature et le chapeau ; le texte qui suit est du Markdown. Chaque ligne de ce paragraphe devient un alinéa.
Une ligne vide sépare deux groupes d'alinéas.

## Du code

Un bloc de code avec une légende devient un listing numéroté. Le code reste du texte : il se lit avec un lecteur d'écran et se copie.

```php title="Listing 1."
<?php

final readonly class Salutation
{
    public function __construct(private string $nom)
    {
    }

    public function texte(): string
    {
        return sprintf('Bonjour, %s !', $this->nom);
    }
}
```

Un bloc `diff` colore les lignes ajoutées et retirées, sans que la couleur soit la seule information :

```diff
- function bonjour($nom) {
+ function bonjour(string $nom): string {
```

## Un tableau

Un tableau a toujours une légende, déclarée sur la ligne qui le précède. Sans elle, la génération échoue.

{legende="Trois formats de contenu et leur usage"}
| Format | Usage |
|---|---|
| Markdown | Le texte des pages |
| YAML | L'en-tête de chaque page et la configuration du site |
| Twig | Les gabarits HTML |

## Un schéma

Un schéma est une image avec un texte alternatif qui le résume, et une description détaillée à déplier.

```schema src="./media/exemple-article/schema.svg" alt="Schéma : un fichier Markdown devient une page HTML, contrôlée avant publication" titre="figure 1"
À gauche, un fichier Markdown. Une flèche le relie au générateur, au centre.

Une seconde flèche relie le générateur à une page HTML, à droite, marquée « contrôlée ».
```

## Les langues et les liens

Une expression laissée en anglais est marquée, pour être prononcée correctement : le principe du [« Keep it simple »]{lang=en}.

Un lien vers un autre site s'ouvre dans un nouvel onglet et l'annonce aux lecteurs d'écran, sans rien ajouter : la [documentation du RGAA](https://accessibilite.numerique.gouv.fr/).

La syntaxe complète est décrite dans `docs/redaction.md`.
