<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Client;

/**
 * Enum representing possible Share Media Categories for LinkedIn posts.
 */
enum ShareMediaCategory: string
{
  case NONE = 'NONE'; // No media attached.
  case IMAGE = 'IMAGE'; // Single image post.
  case VIDEO = 'VIDEO'; // Video post.
  case ARTICLE = 'ARTICLE'; // Shared article or link.
  case DOCUMENT = 'DOCUMENT'; // Uploaded document (e.g., PDF).
  case CAROUSEL = 'CAROUSEL'; // Multiple images (Carousel).
  case URN_REFERENCE = 'URN_REFERENCE'; // Linkedin article with title and body.

  /**
   * Get all enum values as an array.
   *
   * @return string[]
   *   Array of all possible share media categories.
   */
  public static function getValues(): array
  {
    return array_column(ShareMediaCategory::cases(), 'value');
  }

  public function hasThumbnail(): bool
  {
    return match ($this) {
      ShareMediaCategory::IMAGE, ShareMediaCategory::VIDEO, ShareMediaCategory::ARTICLE, ShareMediaCategory::DOCUMENT => true,
      default => false,
    };
  }
}
