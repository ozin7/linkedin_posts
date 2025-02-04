<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Command;

use Drupal\linkedin_posts\Service\LinkedinOauthManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'linkedin_posts:refresh_token',
  description: 'Exchanging a Refresh Token for a New Access Token',
  aliases: ['lprt'],
)]
final class RefreshTokenCommand extends Command {

  /**
   * Constructs a RefreshTokenCommand object.
   */
  public function __construct(
    private readonly LinkedinOauthManager $linkedinPostsOauth,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    // @todo Place your code here.
    $status = $this->linkedinPostsOauth->refreshToken();
    if ($status) {
      $output->writeln('<info>New access token was successfully fetched!</info>');
      return self::SUCCESS;
    } else {
      $output->writeln('<error>Failed to fetch a new access token! Check settings form or cliend credentials</error>');
      return self::FAILURE;
    }
  }
}
