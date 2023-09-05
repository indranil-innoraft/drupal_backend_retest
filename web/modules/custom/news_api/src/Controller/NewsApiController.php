<?php

namespace Drupal\news_api\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for news api publish content.
 */
class NewsApiController extends ControllerBase {

  /**
   * This is entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * It contains the configuration data.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * This constructor will be used for initializing the objects.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   This will be used to fetch the nodes.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),

    );
  }

  /**
   * Return the json responce besed on request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Get the request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return the response based on paramater.
   */
  public function build(Request $request) {
    // Construct the data you want to return in the API response.
    $news_data = [];
    $news_api_config = $this->configFactory->getEditable('news_api.settings');
    $secret_key = $news_api_config->get('secret_key');

    $tag = $request->get('tag');

    if (!empty($tag)) {
      $headers = $request->headers->get('api_key');
      if ($headers === $secret_key) {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      $news_data['message'] = "No news for the Tag was found.";
      return new JsonResponse($news_data);
    }

    $tid_data = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(
          [
            'name' => $tag,
          ]
      );

    if (empty($tid_data)) {
      $news_data['message'] = "No news for the Tag was found.";
    }

    $news_query = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'news')
      ->accessCheck(FALSE);

    $file_storage = $this->entityTypeManager()->getStorage('file');

    if (!empty($tag)) {

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(
          [
            'name' => $tag,
          ]
      );

      $termIds = [];
      foreach ($terms as $term) {
        $termIds[] = $term->id();
      }
      $news_query->condition('field_tags_news', $termIds[0], 'IN');
    }
    $news_id = $news_query->execute();
    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($news_id);

    $news_count = 0;
    foreach ($nodes as $node) {
      $is_published = $node->isPublished();
      if ($is_published) {
        $news_count++;
        $node_images = $node->get('field_images')->getValue();
        unset($iamge_data);
        foreach ($node_images as $image) {
          $image_file = $file_storage->load($image['target_id']);
          $iamge_data[] = $image_file->createFileUrl();
        }
        $timestamp = $node->get('field_published_date')->value;

        $tags = $node->get('field_tags_news')->getValue();
        $tag_names = [];
        $tags_target_id = array_column($tags, 'target_id');
        foreach ($tags_target_id as $tid) {
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
          if ($term) {
            $tag_names[] = $terms->getName();
          }
        }
        $coma_seperated_string_of_tags = implode(',', $tag_names);

        $news_data['data'][] = [
          'title' => $node->getTitle(),
          'body' => $node->get('field_body')->value,
          'summary' => $node->get('field_body')->summary,
          'viwes count' => $node->get('field_views_count')->value,
          'publish date' => $timestamp,
          'tags' => $coma_seperated_string_of_tags,
          'images' => $iamge_data,
        ];
      }

      $news_data['count_data'] = [
        'count' => $news_count,
      ];

    }
    return new JsonResponse($news_data);
  }

}
