---
description: Traduit en français un article déposé dans content/articles/<slug>/ (PDF ou page web)
argument-hint: <slug> [URL de la page web]
---

Traduis en français un article du site. Arguments reçus : `$ARGUMENTS` — le premier est le `<slug>` de l'article, le second (facultatif) est l'adresse d'une page web à traduire.

Lis d'abord ces trois fichiers, puis applique-les à la lettre :

- `docs/redaction.md` : syntaxe du Markdown du site (en-tête YAML, listings, schémas, tableaux, passages en anglais, liens, paragraphes).
- `config/glossaire.yaml` : termes gardés en anglais, traductions imposées, typographie.
- un article déjà publié, comme modèle de ton et de mise en forme : `content/articles/exemple-article/index.fr.md`.

## 1. Trouver la source

Le dossier de l'article est `content/articles/<slug>/`.

- S'il n'existe pas, crée-le avec `php bin/console article:new <slug>` (ajoute `--url=<adresse>` pour une page web), puis continue.
- Source PDF : `content/articles/<slug>/source.pdf`. Lis-le directement, en entier, page par page.
- PDF scanné (pages en image, sans texte) ou lecture directe impossible : rends chaque page en image à 200 dpi environ, par demi-page, et lis ces images. PyMuPDF (`pip install pymupdf` dans un environnement virtuel temporaire) le fait sans installation système.
- Repère d'abord où l'article commence et où il finit : le PDF peut contenir des pages d'un autre article, à ignorer. Si l'article est coupé (renvoi à un listing ou à une partie absente), traduis ce qui est présent, termine `index.fr.md` par un paragraphe en gras « [Traduction incomplète : …] » et signale-le en tête du rapport.
- Un lien dont l'adresse n'est pas lisible dans la source reste du texte simple : n'invente jamais une URL.
- Source web : l'adresse passée en argument, sinon la clé `source.url` de `index.fr.md` si elle désigne un article précis. Récupère la page et ne garde que le corps de l'article (pas le menu, la publicité ni les commentaires).
- S'il n'y a ni PDF ni adresse d'article, arrête-toi et demande la source. N'invente jamais un contenu.

## 2. Écrire `source.en.md`

Le texte anglais complet, en Markdown simple, sans en-tête YAML. Il sert à comparer la traduction à l'original.

- Titres, paragraphes et listes dans l'ordre de l'article.
- Listings de code recopiés **à l'identique**, caractère pour caractère, avec leur légende (« Listing 1. »).
- Pour chaque figure : sa légende et une ligne qui décrit ce qu'elle montre.
- Un passage illisible dans la source est marqué `[ILLISIBLE : …]`. Ne le devine pas.

## 3. Écrire `index.fr.md`

Garde l'en-tête créé par `article:new` et complète-le :

- `titre` : le titre traduit. S'il reste en anglais, ajoute `titre_lang: en`.
- `chapeau` : le paragraphe d'introduction de l'article, traduit.
- `description`, `mots_cles`, `resume` (une phrase, affichée sur l'accueil).
- `source` : `auteur`, et `volume`, `numero`, `date` pour le magazine. Retire les lignes en commentaire devenues inutiles.
- `statut: brouillon` reste **toujours** en place. Seul l'auteur du site publie, après relecture.

Puis le texte, traduit en entier, sans résumer ni couper :

- Français naturel, vouvoiement, phrases reformulées quand le mot à mot sonne faux. Le sens prime.
- Le glossaire s'applique partout. Un terme technique absent du glossaire : choisis l'usage courant et note-le dans le rapport.
- Le code, les URL et les noms propres ne sont pas traduits. Les commentaires du code restent en anglais.
- Les titres commencent à `##`, sans sauter de niveau. Le titre de la page vient de l'en-tête.
- Listing avec légende : ` ```php title="Listing 1." `. Le code n'est jamais une image.
- Un groupe d'alinéas sans espace entre eux : un alinéa par ligne, sans ligne vide. Une ligne vide sépare deux groupes.
- Expression ou titre laissé en anglais : `[texte]{lang=en}`. Les termes de la liste `conserver` du glossaire n'en ont pas besoin.
- Lien : le texte dit où il mène, jamais « cliquez ici » ni « ici ».
- Tableau : toujours précédé de `{legende="…"}`.
- Figure ou schéma : extrais l'image dans `content/articles/<slug>/media/<slug>/` si tu le peux, et écris un bloc ` ```schema ` avec un `alt` qui résume et une description détaillée. Si tu ne peux pas extraire l'image, écris quand même le bloc avec le nom de fichier attendu, et signale-le dans le rapport : la génération échouera tant que l'image manque, c'est voulu.
- Une capture d'écran de code devient un listing en texte.

## 4. Vérifier

1. `php bin/console site:build --brouillons` : la commande doit finir sans erreur. Corrige le Markdown tant qu'elle échoue (sauf pour une image manquante signalée).
2. Compare `source.en.md` et `index.fr.md` : même nombre de titres, de listings, de figures et de tableaux ; aucun paragraphe oublié.
3. Vérifie que chaque listing de `index.fr.md` est identique à celui de `source.en.md`.
4. Relance `composer build` pour que `dist/` ne contienne plus de brouillon.

## 5. Rapport de relecture

Termine par un rapport court, en français :

- **Passages incertains** : la phrase anglaise, la traduction retenue, et pourquoi le doute.
- **Termes hors glossaire** : le terme, la traduction choisie. Propose de les ajouter à `config/glossaire.yaml`.
- **Code** : listings difficiles à lire dans la source, à comparer à l'original.
- **Figures** : images à extraire à la main, descriptions à vérifier.
- **En-tête** : champs laissés vides ou devinés.
- La commande pour prévisualiser : `php bin/console site:build --brouillons`, puis ouvrir `dist/<slug>.html`.

Ne commite rien et ne change pas le statut de l'article.
