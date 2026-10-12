# Guide de rédaction des contenus

Chaque page du site est un fichier Markdown précédé d'un en-tête YAML.

- Page simple : `content/pages/<slug>.md`
- Article : `content/articles/<slug>/index.fr.md` (les images vont dans `content/articles/<slug>/media/`)

Le `<slug>` donne l'adresse de la page : `PHP85` → `https://example.org/PHP85.html`.

Pour voir le résultat : `composer build`, puis ouvrir le fichier dans `dist/`.

## En-tête YAML

```yaml
---
titre: "CQRS and Event Sourcing"   # obligatoire : titre de la page (<h1>)
titre_lang: en                     # si le titre n'est pas en français
titre_menu: "CQRS"                 # libellé plus court pour le menu (facultatif)
type: article                      # accueil | plan | page (par défaut) | article
rubrique: articles                 # obligatoire : general | articles | versions-php | versions-symfony
ordre: 20                          # position dans la rubrique
statut: brouillon                  # brouillon | a-relire | publie (par défaut)
description: "Texte de la balise meta description."
mots_cles: "CQRS, Event Sourcing"
resume: "Une introduction aux concepts de CQRS."   # texte affiché sur l'accueil, après le lien
chapeau: "Paragraphe d'introduction de l'article."
modifie: 2026-09-29                # date de dernière modification (sitemap.xml)
source:
    publication: "La Revue d'exemple"
    url: "https://revue.example.org/"
    auteur: "Ada Exemple"
    volume: 20
    numero: 2
    date: "02/2021"
traduction:
    traducteur: "Alex Exemple"
    relecteur: "Alex Exemple"
---
```

Autres clés :

| Clé | Rôle |
|---|---|
| `slug` | Remplace le nom du fichier ou du dossier comme adresse de la page |
| `titre_page` | Remplace le titre de l'onglet du navigateur (par défaut : « titre – example.org ») |
| `plan: false` | La page n'apparaît pas dans le plan du site |
| `titre_h1` | Titre affiché en tête de page, s'il diffère du `titre` (qui sert au menu, à l'accueil et au plan du site) |
| `profil` | Habillage du texte : `page`, `article`, `version` (note de version) ou `memo`. Par défaut, il dépend du `type` |
| `paragraphes` | `alineas` (par défaut) ou `justifies` : voir « Paragraphes d'un article » |

Un contenu en `brouillon` ou `a-relire` n'est ni généré ni listé. Pour le prévisualiser : `php bin/console site:build --brouillons`.

## Texte

### Titres

Le titre de la page vient de l'en-tête. Dans le texte, les titres commencent à `##`, sans sauter de niveau. Un `#` est refusé.

```markdown
## Section
### Sous-section
```

Pour qu'un titre apparaisse dans le sommaire de la page, lui donner un identifiant :

```markdown
## Types & Variables {#variables}
## Late Static Binding (Résolution statique à la volée) {#late_static_binding sommaire="Late static binding"}
```

`sommaire="…"` donne un libellé plus court pour le sommaire. Deux titres ne peuvent pas avoir le même identifiant.

L'apparence des titres dépend du profil de la page. Pour imposer une classe : `## Titre {.h4}`.

### Code

Le code est toujours du texte, jamais une image.

````markdown
```php
$x = 1;
```
````

Listing numéroté, avec sa légende :

````markdown
```php title="Listing 1."
<?php
class Product {}
```
````

Diff : les lignes qui commencent par `- ` ou `+ ` reçoivent un fond coloré. Le signe reste dans le texte, donc la couleur n'est pas la seule information.

````markdown
```diff
- function passwordHash(string $password) {
+ function passwordHash(#[\SensitiveParameter] string $password) {
```
````

Du code dans une phrase : `` `$variable` ``.

Deux listings avec légende écrits l'un après l'autre sont placés côte à côte quand la largeur de l'écran le permet.

Dans le mémo (`profil: memo`), les commentaires d'un bloc ` ```php ` sont colorés automatiquement. Un bloc sans langage (` ``` ` seul) sert à montrer un résultat affiché : il n'est pas analysé.

### Schémas et images

Un schéma a toujours un texte alternatif (`alt`) qui le résume. S'il est complexe, sa description détaillée s'écrit dans le bloc ; elle apparaît dans une zone à déplier sous l'image.

````markdown
```schema src="./media/exemple-article/schema.svg" alt="Schéma : une même base sert la lecture et l'écriture" titre="figure 1"
L'application cliente envoie la requête POST /v1/users.

Côté COMMAND, la requête appelle User::create().
```
````

- `titre="figure 1"` donne le libellé de la zone à déplier : « Description de la figure 1 ».
- `resume="Transcription de la capture"` remplace ce libellé.
- `lang="en"` indique que la description est en anglais.
- Schéma simple, entièrement décrit par son `alt` : laisser le bloc vide.

Un bloc `schema` sans `src` ou sans `alt` est refusé.

### Tableaux

Un tableau a toujours une légende, déclarée sur la ligne qui le précède. Sans légende, il est refusé.

```markdown
{legende="État de la base de données avant la modification"}
| id | name          | email             |
|----|---------------|-------------------|
| 1  | Ada Exemple | ada@example.org |
```

### Passages en anglais

```markdown
L'initiative « [Meetup in a Box]{lang=en} » a été lancée.
```

À utiliser pour une expression ou une phrase en anglais. Les termes techniques courants (framework, pull request…) n'en ont pas besoin.

### Liens

```markdown
[plan du site](https://example.org/plan_du_site.html)
[Manuel PHP](https://www.php.net/manual/fr/)
```

Un lien vers un autre site s'ouvre automatiquement dans un nouvel onglet, et l'annonce aux lecteurs d'écran : « Manuel PHP – ouvre dans une nouvelle fenêtre (onglet) ». Il n'y a rien à ajouter. Le texte du lien doit dire où il mène : pas de « cliquez ici ».

### Paragraphes d'un article

Dans un article ou une note de version, un paragraphe est justifié et commence par un alinéa. Il n'y a rien à ajouter.

```markdown
**Les Fibers** : cette nouvelle fonctionnalité permet de gérer des threads enfants.
```

Pour un groupe d'alinéas sans espace entre eux, comme dans un article de magazine, écrire **un alinéa par ligne**, sans ligne vide :

```markdown
Premier alinéa du groupe.
Deuxième alinéa, juste en dessous.

Nouveau groupe, après un espace.
```

Deux exceptions, à marquer sur la ligne qui suit le paragraphe :

```markdown
Plus d'info : [gd-avif](https://php.watch/versions/8.1/gd-avif)
{.simple}

Introduction de la page, justifiée mais sans retrait.
{.sans-alinea}
```

- `{.simple}` : paragraphe ordinaire, ni justifié ni en retrait. Utile pour une ligne courte comme « Source : … ».
- `{.sans-alinea}` : paragraphe justifié, sans retrait.

Pour toute une page sans alinéas, mettre `paragraphes: justifies` dans l'en-tête : les paragraphes sont justifiés et séparés par un espace.

Les images d'un article se rangent dans son dossier `media/` et s'écrivent `./media/<dossier>/<fichier>` dans le texte : `content/articles/exemple-article/media/exemple-article/schema.svg` → `./media/exemple-article/schema.svg`.

### Classe sur un paragraphe

```markdown
Page mise à jour le 29 septembre 2026.
{.margin-top-25}
```

## Ce que le générateur fait tout seul

- Menu, accueil, plan du site, `sitemap.xml` et `robots.txt`, à partir des en-têtes.
- Lien d'évitement, zones de la page, page courante signalée dans le menu.
- Nouvel onglet et annonce pour les liens externes.
- `<caption>` et `<th scope="col">` pour les tableaux.
- Nom des fichiers CSS et JS avec empreinte, pour éviter les problèmes de cache.
