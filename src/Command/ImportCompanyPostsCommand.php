<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Command;

use Drupal\linkedin_posts\Service\LinkedinPostsManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'linkedin:company_fetch_all',
  description: '',
  aliases: ['lcfa'],
)]
final class ImportCompanyPostsCommand extends Command {

  /**
   * Constructs a LinkedinPostsCommand object.
   */
  public function __construct(
    private readonly LinkedinPostsManager $linkedinPostsPostsManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $total = $this->linkedinPostsPostsManager->importOrganizationPosts();
    $output->writeln(sprintf('<info>Imported %d posts</info>', $total));
    return self::SUCCESS;
  }

}
