<?php

declare(strict_types=1);

namespace App\Command;

use App\Controle\ControleurAccessibilite;
use Azepo\RgaaCheck\Console\CheckCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'site:check', description: 'Contrôle l\'accessibilité des pages HTML d\'un dossier (par défaut : dist/)')]
final class SiteCheckCommand extends Command
{
    public function __construct(
        private readonly ControleurAccessibilite $controleur,
        #[Autowire('%app.dossier_dist%')]
        private readonly string $dossierDist,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('dossier', InputArgument::OPTIONAL, 'Dossier des pages à contrôler', $this->dossierDist);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dossier = $input->getArgument('dossier');
        $rapport = $this->controleur->controler(\is_string($dossier) ? $dossier : $this->dossierDist);
        CheckCommand::render($io, $rapport);

        return $rapport->passes() ? Command::SUCCESS : Command::FAILURE;
    }
}
