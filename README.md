# Générateur de site statique accessible

Un générateur de site statique en PHP, pensé pour l'accessibilité : chaque page suit les règles du RGAA 4.1 que l'on peut vérifier automatiquement, et **la génération échoue si une page enfreint l'une d'elles**.

Les pages s'écrivent en Markdown, avec un en-tête YAML. Le générateur en fait un site HTML statique, sans JavaScript obligatoire, avec son menu, son accueil, son plan du site, `sitemap.xml` et `robots.txt`.

Il est né pour un site personnel d'articles techniques traduits de l'anglais. Ce dépôt en est la version générique, avec un site d'exemple.

## Ce qu'il fait

- **Contenu en Markdown** : un fichier par page, un dossier par article. Listings de code numérotés, diff, tableaux, schémas avec description, passages dans une autre langue.
- **Navigation générée** : le menu, l'accueil, le plan du site, `sitemap.xml` et `robots.txt` sont construits à partir des en-têtes. Une nouvelle page ne se déclare qu'à un seul endroit : son fichier.
- **Accessibilité contrôlée à chaque génération** : images sans texte alternatif, titres mal hiérarchisés, liens sans intitulé, tableaux sans légende, lien d'évitement absent… Chaque défaut est rattaché à son critère RGAA.
- **Le Markdown refuse les fautes d'accessibilité** : un tableau sans légende ou un schéma sans texte alternatif ne passent pas.
- **Contrôles dans un navigateur**, facultatifs, avec Behat et Chromium : contrastes affichés, affichage à 320 px, lien d'évitement, parcours au clavier, focus visible.
- **Brouillons** : une page en `statut: brouillon` n'est ni générée ni listée, sauf pour la prévisualiser.
- **Traduction assistée**, facultative : une commande pour [Claude Code](https://claude.com/claude-code) traduit un article depuis un PDF ou une page web, en brouillon à relire. Voir « Traduire un article depuis un PDF ».

## Ce qu'il ne fait pas

- Les contrôles automatiques **ne remplacent pas un audit RGAA** et ne permettent pas de déclarer un site conforme. Ils couvrent une trentaine de critères sur 106, et souvent une partie seulement de chacun. Le détail est dans `packages/rgaa-check/docs/regles.md`.
- Pas d'interface d'édition : les contenus s'écrivent dans des fichiers.
- Pas de mise en ligne : le générateur écrit le site dans `dist/`, à envoyer sur votre hébergement (SFTP, rsync…).

## Prérequis

- PHP 8.4 ou plus récent, avec les extensions `dom`, `mbstring` et `ctype`.
- Composer 2.
- Pour les contrôles dans un navigateur seulement : Chromium ou Chrome.

## Démarrage

```bash
composer install
composer build
```

Le site d'exemple est généré dans `dist/`. Pour le voir :

```bash
php -S 127.0.0.1:8000 -t dist
```

puis ouvrir <http://127.0.0.1:8000/>.

## Commandes

| Commande | Rôle |
|---|---|
| `composer build` | Génère le site dans `dist/`, puis contrôle son accessibilité. Échoue en cas d'erreur |
| `php bin/console site:build --brouillons` | Génère aussi les brouillons, pour les relire. Ne pas mettre ce résultat en ligne |
| `composer check` | Contrôle l'accessibilité de `dist/` sans régénérer |
| `php bin/console article:new <slug> --titre="…"` | Prépare un nouvel article en brouillon (`--pdf=…` ou `--url=…` pour un article à traduire) |
| `composer qualite` | Style, analyse statique (PHPStan niveau 10) et tests |
| `vendor/bin/behat` | Contrôles dans un navigateur ; voir `behat.yml` pour les étapes préalables |

## Organisation

| Dossier ou fichier | Contenu |
|---|---|
| `content/pages/<slug>.md` | Les pages simples : accueil, plan du site, accessibilité… |
| `content/articles/<slug>/index.fr.md` | Les articles ; leurs images dans `media/` |
| `config/site.yaml` | Nom et adresse du site, rubriques, ordre du menu, statistiques |
| `templates/` | Gabarits Twig |
| `assets/` | CSS et JavaScript (Bootstrap 5 hébergé localement, sans CDN) |
| `src/` | Le générateur (Symfony Console, Twig, league/commonmark) |
| `packages/rgaa-check/` | Les contrôles d'accessibilité, utilisables seuls sur n'importe quel dossier HTML |
| `packages/rgaa-check-behat/` | Les mêmes contrôles et les mesures dans un navigateur, sous forme d'étapes Behat |
| `docs/redaction.md` | La syntaxe des contenus |

## Écrire une page

```markdown
---
titre: "Ma page"
rubrique: general
description: "Ce que contient la page."
---

Le texte de la page, en Markdown.

## Une section
```

L'adresse de la page vient du nom du fichier : `content/pages/ma-page.md` donne `ma-page.html`. L'article d'exemple, `content/articles/exemple-article/index.fr.md`, montre tout ce qu'un contenu peut contenir. La syntaxe complète est dans [docs/redaction.md](docs/redaction.md).

## Traduire un article depuis un PDF

Le générateur est né pour publier des articles traduits de l'anglais. Le parcours, du PDF à la page publiée, se fait en deux temps : une traduction assistée, qui produit du Markdown, puis la génération habituelle, qui ne lit que ce Markdown.

### Ce qu'il faut

- [Claude Code](https://claude.com/claude-code), qui fait la lecture et la traduction. Le projet fournit la commande `/traduire-article` (`.claude/commands/traduire-article.md`) : aucune clé d'API n'est nécessaire, la traduction utilise votre session Claude Code.
- `poppler-utils` (`sudo apt install poppler-utils` sous Debian ou Ubuntu), pour que Claude Code lise directement les PDF. Un PDF scanné, sans texte, reste lisible : chaque page est alors lue comme une image, plus lentement.

### Les étapes

1. **Créer l'article** à partir du PDF :

   ```bash
   php bin/console article:new Mon_Article --titre="Titre provisoire" --pdf=chemin/vers/article.pdf
   ```

   La commande crée `content/articles/Mon_Article/`, y copie le PDF sous le nom `source.pdf`, et écrit un `index.fr.md` en brouillon. Pour une page web, remplacer `--pdf=…` par `--url=…`.

2. **Traduire**, dans Claude Code :

   ```
   /traduire-article Mon_Article
   ```

   Claude Code lit le PDF et écrit deux fichiers :
   - `source.en.md`, le texte d'origine en Markdown, listings de code recopiés à l'identique ;
   - `index.fr.md`, la traduction, avec son en-tête complété et toujours en `statut: brouillon`.

   Il termine par un rapport de relecture : passages incertains, termes absents du glossaire (`config/glossaire.yaml`), code difficile à lire dans le PDF, images à extraire, pages manquantes.

3. **Relire et prévisualiser** :

   ```bash
   php bin/console site:build --brouillons
   ```

   puis ouvrir `dist/Mon_Article.html`. Comparer la traduction au PDF, et surtout le code, qui doit être identique à l'original.

4. **Publier** : retirer la ligne `statut: brouillon` de l'en-tête, puis `composer build`. L'article apparaît dans le menu, sur l'accueil, dans le plan du site et dans `sitemap.xml`.

### À savoir

- **La traduction est toujours un brouillon.** Rien n'est publié sans relecture humaine, et la commande ne change jamais le statut d'un article.
- **Le PDF n'est jamais publié** : le générateur l'ignore, et le `.gitignore` l'écarte du dépôt.
- **Droits** : traduire et publier un article dont vous n'êtes pas l'auteur demande l'accord de son auteur ou de son éditeur. L'outil ne le vérifie pas.
- **Images** : les schémas d'un PDF ne sont pas toujours extraits automatiquement. Le rapport de relecture liste ceux à copier à la main dans le dossier `media/` de l'article ; la génération échoue tant qu'une image annoncée manque, c'est voulu.
- **PDF incomplet** : si l'article est coupé (pages manquantes), la traduction s'arrête au même endroit et le signale par un paragraphe « Traduction incomplète ».

## Adapter le site

- **Nom, adresse, rubriques, menu** : `config/site.yaml`. L'adresse du site d'exemple est celle du serveur local, `http://127.0.0.1:8000` : le menu, les liens internes et `sitemap.xml` en dépendent. La remplacer par l'adresse réelle avant la mise en ligne.
- **Apparence** : `assets/accueil.css` et les gabarits de `templates/`. Toute couleur ajoutée à la feuille de style doit être déclarée dans `src/Controle/Palette.php` avec son contraste : un test échoue sinon.
- **Statistiques** : section `matomo` de `config/site.yaml`, facultative.

## Licence

MIT. Voir [LICENSE](LICENSE).
