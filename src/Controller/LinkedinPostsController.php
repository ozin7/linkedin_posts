<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Controller;

use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use League\OAuth2\Client\Provider\LinkedIn;
use Symfony\Component\HttpFoundation\RequestStack;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\linkedin_posts\Service\LinkedinOauthManager;
use Drupal\linkedin_posts\Service\LinkedinPostsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Linkedin posts routes.
 */
final class LinkedinPostsController extends ControllerBase
{

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly LinkedinOauthManager $linkedinOauthManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self
  {
    return new self(
      $container->get('request_stack'),
      $container->get('linkedin_posts.oauth')
    );
  }

  /**
   * Builds the response.
   */
  public function getToken(): RedirectResponse
  {
    $currentRequest = $this->requestStack->getCurrentRequest();
    $code = $currentRequest->get('code');
    $token = $this->linkedinOauthManager->getAccessToken($code);
    if ($token instanceof AccessTokenInterface) {
      $this->linkedinOauthManager->setTokenData($token);
    }
    $url = Url::fromRoute('linkedin_posts.settings')->toString();
    return new RedirectResponse($url);
  }

  public function access(AccountInterface $account) {
    $currentRequest = $this->requestStack->getCurrentRequest();
    $state = $currentRequest->get('state');
    return AccessResult::allowedIf($state === LinkedinOauthManager::SECURE_STATE);
  }

}
