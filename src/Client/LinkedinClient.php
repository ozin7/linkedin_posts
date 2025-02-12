<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Client;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\linkedin_posts\Service\LinkedinOauthManager;

/**
 * Linkedin client.
 */
class LinkedinClient {

  public function __construct(
    private readonly ClientInterface $client,
    private readonly LinkedinOauthManager $oauthManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Get organization posts.
   */
  public function getOrganizationPosts(string $organizationId): array {
    $token = $this->oauthManager->getToken();
    if (!$token) {
      return [];
    }

    $urn = "urn:li:organization:$organizationId";
    $encodedUrn = rawurlencode($urn);
    try {
      $response = $this->client->request('GET', 'https://api.linkedin.com/v2/ugcPosts?q=authors&authors=List(' . $encodedUrn . ')&sortBy=CREATED', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'X-Restli-Protocol-Version' => '2.0.0',
        ],
      ]);

      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching LinkedIn posts: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get multiple photos.
   * Require r_compliance permisisons.
   */
  public function getMultiplePhotos(string $postUrn): array {
    $token = $this->oauthManager->getToken();
    if (!$token) {
      return [];
    }
    $encodedUrn = rawurlencode($postUrn);
    try {
      $response = $this->client->request('GET', 'https://api.linkedin.com/v2/ugcPosts/' . $encodedUrn . '?projection=(specificContent(com.linkedin.ugc.ShareContent(media*(media~multiPhoto(multiPhotoEntities*(actionableContent(content(value(com.linkedin.content.MediaContent(mediaType,media~digitalmediaAsset:playableStreams))))))))))', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'X-Restli-Protocol-Version' => '2.0.0',
        ],
      ]);

      return json_decode($response->getBody()->getContents(), TRUE);
    } catch (\Exception $e) {
      $this->logger->error('Error fetching LinkedIn posts: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    return  [];
  }

}
