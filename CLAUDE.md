# Générateur de site statique accessible

Générateur de site statique en PHP : pages en Markdown + en-tête YAML, gabarits Twig, site HTML statique dans `dist/`, contrôles RGAA 4.1 à chaque génération.

## Règles non négociables

- **Accessibilité RGAA 4.1** : la génération doit échouer si un contrôle d'accessibilité échoue. Ne jamais contourner un contrôle pour faire passer une page.
- **Responsive** : chaque page reste utilisable à 320 px de large et avec un zoom à 200 %, sans défilement horizontal.
- **Sans JavaScript obligatoire** : le JavaScript ne sert qu'à améliorer (menu Bootstrap, surlignage du sommaire).
- PHP 8.4 ou plus récent, `declare(strict_types=1)`, PHPStan niveau 10.

## Conventions des pages

- Un seul `<h1>` par page, puis des titres sans saut de niveau.
- Lien d'évitement « Aller au contenu » en premier, vers `<main id="contenu" tabindex="-1">`.
- Code toujours en texte, jamais en image. Schéma : `alt` qui résume + description dans `<details>`.
- Passages dans une autre langue : `lang`. Lien qui ouvre un nouvel onglet : l'annoncer dans son `aria-label`.
- Couleurs : uniquement celles déclarées dans `src/Controle/Palette.php`, avec leur contraste.

## Commandes

- `composer build` : génère le site dans `dist/` et contrôle son accessibilité.
- `php bin/console site:build --brouillons` : génère aussi les brouillons.
- `composer qualite` : PHP-CS-Fixer, PHPStan et PHPUnit, sur le générateur et les deux paquets. À lancer avant de considérer un changement comme terminé.
- `php bin/console article:new <slug>` : prépare un article en brouillon.
- `/traduire-article <slug> [URL]` (`.claude/commands/traduire-article.md`) : traduit un article en brouillon. Ne jamais changer le statut d'un article à la place de l'utilisateur.

## Organisation

- Contenu : `content/pages/<slug>.md`, `content/articles/<slug>/index.fr.md`. Syntaxe : `docs/redaction.md`.
- Configuration : `config/site.yaml`. Gabarits : `templates/`. Code : `src/`. Tests : `tests/`.
- Contrôles d'accessibilité : `packages/rgaa-check/` (une règle = une classe de `src/Rule/`, avec sa page fautive dans les tests et sa ligne dans `docs/regles.md`).
- Contrôles dans un navigateur : `packages/rgaa-check-behat/`, lancés par `vendor/bin/behat` (voir `behat.yml`).
