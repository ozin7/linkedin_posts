<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Client;

/**
 * Enum representing possible Share Media Categories for LinkedIn posts.
 */
enum ShareMediaCategory: string {
  // No media attached.
  case NONE = 'NONE';
  // Single image post.
  case IMAGE = 'IMAGE';
  // Video post.
  case VIDEO = 'VIDEO';
  // Video post.
  case LIVE_VIDEO = 'LIVE_VIDEO';
  // Shared article or link.
  case ARTICLE = 'ARTICLE';
  // Uploaded document (e.g., PDF).
  case DOCUMENT = 'DOCUMENT';
  // Multiple images (Carousel).
  case CAROUSEL = 'CAROUSEL';
  // Linkedin article with title and body.
  case URN_REFERENCE = 'URN_REFERENCE';
  case RICH = 'RICH';
  case LEARNING_COURSE = 'LEARNING_COURSE';
  case JOB = 'JOB';
  case QUESTION = 'QUESTION';
  case ANSWER = 'ANSWER';
  case TOPIC = 'TOPIC';
  case NATIVE_DOCUMENT = 'NATIVE_DOCUMENT';

  /**
   * Get all enum values as an array.
   *
   * @return string[]
   *   Array of all possible share media categories.
   */
  public static function getValues(): array {
    return array_column(ShareMediaCategory::cases(), 'value');
  }

  /**
   *
   */
  public function hasThumbnail(): bool {
    return match ($this) {
      ShareMediaCategory::IMAGE, ShareMediaCategory::VIDEO, ShareMediaCategory::ARTICLE, ShareMediaCategory::DOCUMENT => TRUE,
      default => FALSE,
    };
  }

}
