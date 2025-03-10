<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Service;

use Psr\Log\LoggerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\linkedin_posts\Client\LinkedinClient;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\linkedin_posts\Client\ShareMediaCategory;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Linkedin posts manager.
 */
class LinkedinPostsManager {

  public const LINKEDIN_CONTENT_TYPE = 'linkedin_post';

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
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->config = $this->configFactory->get($configName);
  }

  /**
   * Import organization posts.
   */
  public function importOrganizationPosts(): int {
    $imported = 0;
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $organizationId = $this->config->get('organization_id');
    if ($organizationId) {
      $posts = $this->linkedinClient->getOrganizationPosts($organizationId);
      if (!empty($posts['elements'])) {
        foreach ($posts['elements'] as $post) {
          if ($this->postExists($post['id'])) {
            continue;
          }
          $values = $this->prepareValues($post);
          if ($values) {
            $newPost = $nodeStorage->create($values);
            $newPost->save();
            $imported++;
          }
        }
      }
    }

    return $imported;
  }

  /**
   * Truncate words.
   */
  private function truncateWords($text, $limit = 5, $suffix = '...') {
    $words = explode(' ', strip_tags($text));
    if (count($words) > $limit) {
      return implode(' ', array_slice($words, 0, $limit)) . $suffix;
    }

    return $text;
  }

  /**
   * Check if post exists.
   */
  private function postExists($postId): bool {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('field_post_id', $postId);
    $query->accessCheck(TRUE);
    return (bool) $query->execute();
  }

  /**
   * Prepare values.
   */
  private function prepareValues(array $post): array
  {
    $shareContent = $post['specificContent']['com.linkedin.ugc.ShareContent'];
    $media = $shareContent['media'];
    $sharedMediaCategory = ShareMediaCategory::from($shareContent['shareMediaCategory']);
    $body = $shareContent['shareCommentary']['text'];
    $date = DrupalDateTime::createFromTimestamp((int) ((int) $post['firstPublishedAt'] / 1000));
    $title = $this->truncateWords($body);
    $cookedTitle = sprintf('%s - %s', $date->format('d.m.Y'), $title);
    $values = [
      'created' => $date->getTimestamp(),
      'type' => self::LINKEDIN_CONTENT_TYPE,
      'title' => $cookedTitle,
      'body' => $body,
      'field_post_id' => $post['id'],
      'field_post_date' => $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      'field_embedded_image' => $sharedMediaCategory === ShareMediaCategory::IMAGE,
    ];
    if (!empty($media)) {
      $firstMedia = reset($media);
      if (!empty($firstMedia['thumbnails'])) {
        $firstThumbnail = reset($firstMedia['thumbnails']);
        $values['field_thumbnail'] = $firstThumbnail['url'];
      }
    }
    // Process LinkedIn articles which have extra entity.
    if ($sharedMediaCategory == ShareMediaCategory::URN_REFERENCE) {
      if (isset($media[0]['media'])) {
        $mediaId = $media[0]['media'];
        if (str_starts_with($mediaId, 'urn:li:multiPhoto:')) {
          // $photos = $this->linkedinClient->getMultiplePhotos($mediaId);
        }
      }
    }
    return $values;
  }

}
