<?php

declare(strict_types=1);

namespace App\Command;

use App\Contenu\CreateurArticle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'article:new', description: 'Prépare le dossier d\'un nouvel article à traduire (brouillon)')]
final class ArticleNewCommand extends Command
{
    public function __construct(private readonly CreateurArticle $createur)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('slug', InputArgument::REQUIRED, 'Adresse de la page : Mon_Article donne Mon_Article.html')
            ->addOption('titre', null, InputOption::VALUE_REQUIRED, 'Titre de l\'article (par défaut : le slug, à corriger ensuite)')
            ->addOption('rubrique', null, InputOption::VALUE_REQUIRED, 'Rubrique du site', 'articles')
            ->addOption('pdf', null, InputOption::VALUE_REQUIRED, 'PDF à traduire : il est copié dans le dossier de l\'article (source.pdf)')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Adresse de la page web à traduire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = $this->texte($input->getArgument('slug')) ?? '';

        try {
            $fichier = $this->createur->creer(
                $slug,
                $this->texte($input->getOption('titre')) ?? str_replace(['_', '-'], ' ', $slug),
                $this->texte($input->getOption('rubrique')) ?? 'articles',
                new \DateTimeImmutable('today'),
                $this->texte($input->getOption('url')),
                $this->texte($input->getOption('pdf')),
            );
        } catch (\InvalidArgumentException $erreur) {
            $io->error($erreur->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Article créé en brouillon : %s', $fichier));
        $io->writeln([
            'Étapes suivantes :',
            sprintf('  1. Dans Claude Code : /traduire-article %s', $slug),
            '  2. Relire la traduction, compléter l\'en-tête.',
            '  3. Prévisualiser : php bin/console site:build --brouillons',
            '  4. Publier : retirer « statut: brouillon », puis composer build',
        ]);

        return Command::SUCCESS;
    }

    private function texte(mixed $valeur): ?string
    {
        return \is_string($valeur) && '' !== trim($valeur) ? trim($valeur) : null;
    }
}
