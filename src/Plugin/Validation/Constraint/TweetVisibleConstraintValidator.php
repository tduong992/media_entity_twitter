<?php

/**
 * @file
 * Contains \Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetVisibleConstraintValidator.
 */

namespace Drupal\media_entity_twitter\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\media_entity_twitter\Plugin\MediaEntity\Type\Twitter;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TweetVisible constraint.
 */
class TweetVisibleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new TweetVisibleConstraintValidator.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The http client service.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }


  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    $matches = [];

    foreach (Twitter::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $entity->value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    // fetch content from the given url
    $response = $this->httpClient->get($matches[0][0], ['allow_redirects' => FALSE]);

    if ($response->getStatusCode() == 302 && ($location = $response->getHeader('location'))) {
      $effective_url_parts = parse_url($location[0]);
      if (!empty($effective_url_parts) && isset($effective_url_parts['query']) && $effective_url_parts['query'] == 'protected_redirect=true') {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
