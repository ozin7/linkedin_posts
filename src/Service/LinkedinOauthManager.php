<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Service;

use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Drupal\Core\State\StateInterface;
use League\OAuth2\Client\Provider\LinkedIn;
use Drupal\Core\Config\ConfigFactoryInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

class LinkedinOauthManager
{
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
    private string $configName
  ) {
    $this->config = $this->configFactory->get($configName);
  }

  public function getClientId(): ?string
  {
    return $this->config->get('client_id') ?: null;
  }

  public function getClientSecret(): ?string
  {
    return $this->config->get('client_secret') ?: null;
  }

  public function getRedirectUrl(): ?string
  {
    return Url::fromRoute('linkedin_posts.token', [], ['absolute' => true])->toString();
  }

  /**
   * Get Linkedin provider.
   */
  public function getLinkedInProvider(): ?LinkedIn
  {
    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();
    if (!$clientId || !$clientSecret) return null;

    return new LinkedIn([
      'clientId' => $clientId,
      'clientSecret' => $clientSecret,
      'redirectUri' => $this->getRedirectUrl(),
    ]);
  }

  public function getAccessToken(string $code): ?AccessTokenInterface
  {
    $provider = $this->getLinkedInProvider();
    if (!$provider) return null;

    try {
      return $provider->getAccessToken('authorization_code', [
        'code' => $code
      ]);
    } catch (\Throwable $e) {
      $this->logger->error($e->getMessage());
    }
  }
  public function setTokenData(AccessTokenInterface $token): void
  {
    $tokenData = [
      'token' => $token->getToken(),
      'expires' => $token->getExpires(),
      'refreshToken' => $token->getRefreshToken(),
      'value' => $token->getValues(),
    ];
    $this->state->set(self::TOKEN_STORAGE, $tokenData);
  }
  public function getTokenData(): ?array
  {
    return $this->state->get(self::TOKEN_STORAGE) ?: null;
  }
  public function getToken(): ?string
  {
    $data = $this->getTokenData();
    if (!$data) return null;

    if ($this->tokenIsExpired($data)) return null;

    return $data ? $data['token'] : null;
  }

  private function tokenIsExpired(array $tokenData): bool
  {
    if ($tokenData['expires'] < time()) {
      return true;
    }

    return false;
  }
}
