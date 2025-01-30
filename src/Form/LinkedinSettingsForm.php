<?php

declare(strict_types=1);

namespace Drupal\linkedin_posts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\linkedin_posts\Service\LinkedinOauthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Linkedin posts settings for this site.
 */
final class LinkedinSettingsForm extends ConfigFormBase
{

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected $typedConfigManager,
    private readonly LinkedinOauthManager $linkedinOauthManager
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('linkedin_posts.oauth'),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'linkedin_posts_linkedin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array
  {
    return ['linkedin_posts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->configFactory->get('linkedin_posts.settings');
    $form['organization_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization Id'),
      '#default_value' => $config->get('organization_id'),
      '#required' => TRUE,
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#default_value' => $this->linkedinOauthManager->getClientId(),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $this->linkedinOauthManager->getClientSecret(),
      '#required' => TRUE,
    ];

    $tokenData = $this->linkedinOauthManager->getTokenData();
    if (!empty($tokenData['expires'])) {
      $expirationDate = DrupalDateTime::createFromTimestamp($tokenData['expires']);
      $form['token_expiration'] = [
        '#type' => 'item',
        '#title' => $this->t('Token Expiration Date'),
        '#markup' => $this->t('The access token expires on: @date', ['@date' => $expirationDate->format('d.m.Y H:i:s')]),
      ];
      $form['token_scope'] = [
        '#type' => 'item',
        '#title' => $this->t('Token Scope'),
        '#markup' => implode(', ', LinkedinOauthManager::SCOPE),
      ];
    }
    $form['linkedin_redirects'] = [
      '#type' => 'item',
      '#title' => $this->t('LinkedIn App OAuth 2.0 settings'),
      '#markup' => $this->t('Add Authorized redirect URLs to your LinkedIn app settings: @redirect', ['@redirect' => $this->linkedinOauthManager->getRedirectUrl()]),
    ];
    $form['actions']['get_auth_token'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fetch Access Token'),
      '#submit' => ['::getNewLinkedinToken'],
      '#button_type' => 'secondary',
      '#weight' => 100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $values = $form_state->cleanValues()->getValues();
    $this->config('linkedin_posts.settings')
      ->setData($values)
      ->save();
    parent::submitForm($form, $form_state);
  }

  public function getNewLinkedinToken(array &$form, FormStateInterface $form_state)
  {
    $this->submitForm($form, $form_state);
    $config = $this->config('linkedin_posts.settings');
    if ($config->get('client_id') && $config->get('client_secret')) {
      $provider = $this->linkedinOauthManager->getLinkedInProvider();
      $options = [
        'state' => LinkedinOauthManager::SECURE_STATE,
        'scope' => LinkedinOauthManager::SCOPE,
      ];
      $authUrl = $provider->getAuthorizationUrl($options);
      $_SESSION['oauth2state'] = $provider->getState();
      $form_state->setResponse(new TrustedRedirectResponse($authUrl));
    } else {
      $this->messenger()->addWarning($this->t('Please provide client id and client secret or save the configuration first.'));
    }
  }

}
