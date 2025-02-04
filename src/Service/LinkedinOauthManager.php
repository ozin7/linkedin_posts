<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Service;

use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Drupal\Core\State\StateInterface;
use League\OAuth2\Client\Provider\LinkedIn;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Linkedin Oauth manager.
 */
class LinkedinOauthManager {
  public const SECURE_STATE = 'linkedin.token.state';
  public const TOKEN_STORAGE = 'linkedin_posts.token';
  public const SCOPE = ['r_organization_social'];

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly StateInterface $state,
    private readonly LoggerInterface $logger,
    private string $configName,
  ) {
    $this->config = $this->configFactory->get($configName);
  }

  /**
   * Get client id.
   */
  public function getClientId(): ?string {
    return $this->config->get('client_id') ?: NULL;
  }

  /**
   * Get client secret.
   */
  public function getClientSecret(): ?string {
    return $this->config->get('client_secret') ?: NULL;
  }

  /**
   * Get redirect URL.
   */
  public function getRedirectUrl(): ?string {
    return Url::fromRoute('linkedin_posts.token', [], ['absolute' => TRUE])->toString();
  }

  /**
   * Get Linkedin provider.
   */
  public function getLinkedInProvider(): ?LinkedIn {
    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();
    if (!$clientId || !$clientSecret) {
      return NULL;
    }

    try {
      return new LinkedIn([
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri' => $this->getRedirectUrl(),
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error($e->getMessage());
    }

    return NULL;
  }

  /**
   * Get access token.
   */
  public function getAccessToken(string $code): ?AccessTokenInterface {
    $provider = $this->getLinkedInProvider();
    if (!$provider) {
      return NULL;
    }

    try {
      return $provider->getAccessToken('authorization_code', [
        'code' => $code,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Set token data.
   */
  public function setTokenData(AccessTokenInterface $token): void {
    $tokenData = [
      'token' => $token->getToken(),
      'expires' => $token->getExpires(),
      'refreshToken' => $token->getRefreshToken(),
      'value' => $token->getValues(),
    ];
    $this->state->set(self::TOKEN_STORAGE, $tokenData);
  }

  /**
   * Get token data.
   */
  public function getTokenData(): ?array {
    return $this->state->get(self::TOKEN_STORAGE) ?: NULL;
  }

  /**
   * Get token.
   */
  public function getToken(): ?string {
    $data = $this->getTokenData();
    if (!$data) {
      return NULL;
    }

    if ($this->tokenIsExpired($data)) {
      return NULL;
    }

    return $data ? $data['token'] : NULL;
  }

  /**
   * Check if token is expired.
   */
  public function tokenIsExpired(array $tokenData): bool {
    $expireDate = DrupalDateTime::createFromTimestamp($tokenData['expires']);
    $now = new DrupalDateTime();
    return $expireDate->getTimestamp() < $now->getTimestamp();
  }

  /**
   * Get new access token with refresh token.
   */
  public function getNewAccessTokenWithRefreshToken(array $tokenData): ?AccessTokenInterface {
    $provider = $this->getLinkedInProvider();
    if (!$provider) {
      return NULL;
    }
    $params = [
      'refresh_token' => $tokenData['refreshToken'],
      'client_id' =>  $this->getClientId(),
      'client_secret' => $this->getClientSecret(),
    ];
    try {
      return $provider->getAccessToken('refresh_token', $params);
    } catch (\Throwable $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Refresh token.
   */
  public function refreshToken(): bool {
    $tokenData = $this->getTokenData();
    if (!$tokenData) return FALSE;
    $newToken = $this->getNewAccessTokenWithRefreshToken($tokenData);
    if (!$newToken) return FALSE;

    $this->setTokenData($newToken);
    return TRUE;
  }

  /**
   * Check if token needs refresh.
   */
  public function needTokenRefresh(): bool {
    $tokenData = $this->getTokenData();
    if (!$tokenData) return FALSE;
    return $this->tokenIsExpired($tokenData);
  }

}
