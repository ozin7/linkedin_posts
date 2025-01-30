<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Service;

use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\linkedin_posts\Client\LinkedinClient;

class LinkedinPostsManager
{
  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  public function __construct(
    private readonly LinkedinClient $linkedinClient,
    private readonly LoggerInterface $logger,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly string $configName,
    private readonly EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->config = $this->configFactory->get($configName);
  }

  public function importOrganizationPosts(): int
  {
    $imported = 0;
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $organizationId = $this->config->get('organization_id');
    if ($organizationId) {
      $posts = $this->linkedinClient->getOrganizationPosts($organizationId);
      if (!empty($posts['elements'])) {
        foreach ($posts['elements'] as $post) {
          $body = $post['specificContent']['com.linkedin.ugc.ShareContent']['shareCommentary']['text'];
          $title = $this->truncateWords($body);
          $values = [
            'created' => (int) ((int) $post['firstPublishedAt'] / 1000),
            'type' => 'linkedin_post',
            'title' => $title,
            'body' => $body,
            'field_post_id' => $post['id'],
          ];
          $newPost = $nodeStorage->create($values);
          $newPost->save();
          $imported++;
        }
      }
    }

    return $imported;
  }

  function truncateWords($text, $limit = 5, $suffix = '...')
  {
    $words = explode(' ', strip_tags($text)); // Remove HTML tags and split into words.

    if (count($words) > $limit) {
      return implode(' ', array_slice($words, 0, $limit)) . $suffix;
    }

    return $text; // Return full text if it's within the limit.
  }
}
