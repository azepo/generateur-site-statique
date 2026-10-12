<?php

declare(strict_types=1);

namespace App\Contenu\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;

/**
 * Rend les blocs de code délimités par ```.
 *
 *   ```php                         bloc simple            -> <pre class="listing"><code>
 *   ```php title="Listing 1."      listing avec légende   -> <figure class="listing"><figcaption>
 *   ```diff                        lignes « - » et « + »  -> fonds colorés, signes conservés
 *   ```schema src="…" alt="…"      image + description    -> <figure class="schema"><details>
 *
 * Le code n'est jamais coloré par une classe de langage : il reste du texte lisible et copiable.
 * Seule exception, le mémo : les commentaires du code PHP reçoivent la classe « commentaire ».
 * Le signe du commentaire (//, #, /*) reste dans le texte : la couleur n'est pas la seule information.
 */
final readonly class BlocCodeRenderer implements NodeRendererInterface
{
    /**
     * @param \Closure(string): string $markdown rend en HTML le texte d'une description de schéma
     */
    public function __construct(
        private ?string $classeBlocCode,
        private \Closure $markdown,
        private bool $commentairesColores = false,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof FencedCode) {
            throw new \InvalidArgumentException(sprintf('Nœud inattendu : %s.', $node::class));
        }

        $info = trim((string) $node->getInfo());
        $langage = strtok($info, " \t") ?: '';
        $attributs = $this->attributs($info);
        $code = rtrim($node->getLiteral(), "\n");

        if ('schema' === $langage) {
            return $this->schema($attributs, $code);
        }

        if ($this->commentairesColores && 'php' === $langage) {
            $pre = '<code>'.$this->commentairesPhp($code).'</code></pre>';
        } else {
            $lignes = array_map(Xml::escape(...), explode("\n", $code));
            if ('diff' === $langage) {
                $lignes = array_map($this->ligneDiff(...), $lignes);
            }
            $pre = '<code>'.implode("\n", $lignes).'</code></pre>';
        }

        if (isset($attributs['title'])) {
            return sprintf("<figure class=\"listing\">\n<figcaption>%s</figcaption>\n<pre>%s\n</figure>", Xml::escape($attributs['title']), $pre);
        }

        return (null === $this->classeBlocCode ? '<pre>' : sprintf('<pre class="%s">', $this->classeBlocCode)).$pre;
    }

    /**
     * Entoure chaque commentaire PHP d'un <span class="commentaire">.
     *
     * L'analyseur lexical de PHP repère les commentaires : un « // » dans une chaîne n'en est pas un.
     */
    private function commentairesPhp(string $code): string
    {
        // Un extrait ne commence pas toujours par la balise d'ouverture : on l'ajoute le temps de l'analyse.
        $ouverture = str_starts_with(ltrim($code), '<?php') ? '' : "<?php\n";
        $html = '';
        foreach (\PhpToken::tokenize($ouverture.$code) as $indice => $jeton) {
            if ('' !== $ouverture && 0 === $indice) {
                continue;
            }
            $texte = Xml::escape($jeton->text);
            if (!$jeton->is([\T_COMMENT, \T_DOC_COMMENT])) {
                $html .= $texte;
                continue;
            }
            // Le retour à la ligne final d'un commentaire « // » reste hors du <span>.
            $fin = str_ends_with($texte, "\n") ? "\n" : '';
            $html .= '<span class="commentaire">'.rtrim($texte, "\n").'</span>'.$fin;
        }

        return $html;
    }

    private function ligneDiff(string $ligne): string
    {
        return match (true) {
            str_starts_with($ligne, '- ') => '<span class="diff-suppr">'.$ligne.'</span>',
            str_starts_with($ligne, '+ ') => '<span class="diff-ajout">'.$ligne.'</span>',
            default => $ligne,
        };
    }

    /**
     * @param array<string, string> $attributs
     */
    private function schema(array $attributs, string $description): string
    {
        $src = $attributs['src'] ?? throw new MarkdownInvalide('Un bloc « schema » doit indiquer son image : src="…".');
        $alt = $attributs['alt'] ?? '';
        if ('' === trim($alt)) {
            throw new MarkdownInvalide(sprintf('Le schéma « %s » doit avoir un texte alternatif : alt="…".', $src));
        }

        $image = sprintf('<img src="%s" alt="%s">', Xml::escape($src), Xml::escape($alt));
        if ('' === trim($description)) {
            return sprintf("<figure class=\"schema\">\n%s\n</figure>", $image);
        }

        $resume = $attributs['resume'] ?? (isset($attributs['titre']) ? 'Description de la '.$attributs['titre'] : 'Description du schéma');
        $corps = trim(($this->markdown)($description));
        if (isset($attributs['lang'])) {
            $corps = sprintf('<div lang="%s">%s</div>', Xml::escape($attributs['lang']), $corps);
        }

        return sprintf(
            "<figure class=\"schema\">\n%s\n<figcaption><details><summary>%s</summary>%s</details></figcaption>\n</figure>",
            $image,
            Xml::escape($resume),
            $corps,
        );
    }

    /**
     * Attributs de la ligne d'ouverture : cle="valeur".
     *
     * @return array<string, string>
     */
    private function attributs(string $info): array
    {
        preg_match_all('/([a-z]+)="([^"]*)"/', $info, $trouves, \PREG_SET_ORDER);

        return array_column($trouves, 2, 1);
    }
}
