<?php

namespace Drupal\node_visited_count;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Perform operation.
 */
class NodeVisitedCount {

  /**
   * It contains the node storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a NodeVisitedCount object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Increment the the node visited count by one.
   *
   * @param int $node_id
   *   It contains the node id.
   *
   * @return void
   *   It increment the node visited count field.
   */
  public function incrementCount(int $node_id) {
    $news_node = $this->entityTypeManager->getStorage('node')->load($node_id);
    $node_views_count = $this->getViewsCount($node_id);
    $node_views_count++;
    $news_node->set('field_views_count', $node_views_count);
    $news_node->save();
  }

  /**
   * Get the like count value.
   *
   * @param int $node_id
   *   The node id.
   *
   * @return int
   *   The nove visited count.
   */
  protected function getViewsCount(int $node_id) {
    // Load the node and retrieve the views count value.
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    return $node->get('field_views_count')->value;
  }

}
