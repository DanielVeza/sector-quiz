<?php

namespace Drupal\sector_quiz\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'QuizQueuePaginationBlock' block.
 *
 * @Block(
 *  id = "quiz_queue_pagination_block",
 *  admin_label = @Translation("Quiz queue pagination block"),
 * )
 */
class QuizQueuePaginationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new QuizQueuePaginationBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FileSystemInterface $file_system,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;

  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $validQueues = [];
    if ($node instanceof NodeInterface) {
      // Get nid from the node object.
      $nid = $node->id();
      // Get all queues this node is a part of.
      $queues = $this->getAvailableQueuesForEntity($node);
      $subqueues = $this->entityTypeManager->getStorage('entity_subqueue')->loadByProperties(['queue' => array_keys($queues)]);
      /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
      foreach ($subqueues as $subqueue_id => $subqueue) {
        // Get all queue items.
        $subqueue_items = $subqueue->get('items')->getValue();
        // Check if the node is in the queue.
        if (in_array($node->id(), array_column($subqueue_items, 'target_id'), TRUE)) {
          // Get the parent queue ID. We only care about quizzes.
          $queueId = $subqueue->getQueue()->id();
          $queuePosition = 0;
          if ($queueId == 'quizzes') {
            //$terms = $this->entityTypeManager->getStorage('node')->loadByProperties(['field_scenario_quiz' => 29]);
            $terms = $node->get('field_scenario_quiz')->getValue();

            if (!empty($terms)) {
              foreach ($subqueue_items as $key => $item) {
                if ($nid == $item['target_id']) {
                  $queuePosition = $key;
                }
              }
              $term = array_shift($terms);
              $tid = $term['target_id'];
              $term = Term::load($tid);
              $nextPageNid =  (isset($subqueue_items[$queuePosition + 1]['target_id'])) ? $subqueue_items[$queuePosition + 1]['target_id'] : $nid;
              $nextPageNode = Node::load($nextPageNid);
              $nextPageUrl = ($nextPageNode !== $node) ? $nextPageNode->url() : null;
              $prevPageNid =  (isset($subqueue_items[$queuePosition - 1]['target_id'])) ? $subqueue_items[$queuePosition - 1]['target_id'] : $nid;
              $prevPageNode = Node::load($prevPageNid);
              $prevPageUrl = ($prevPageNode !== $node) ? $prevPageNode->url() : null;
              $validQueues[] = [
                'quiz_url' => $term->url(),
                'quiz_name' => $term->label(),
                'next_item_url' => $nextPageUrl,
                'next_item_name' => $nextPageNode->getTitle(),
                'prev_item_url' => $prevPageUrl,
                'prev_item_name' => $prevPageNode->getTitle(),
              ];
            }
          }
        }
      }
    }
    if (empty($validQueues)) {
      return [];
    }
    return [
      '#theme' => 'quiz_queue_pagination',
      '#queues' => $validQueues,
    ];

  }

  /**
   * Gets a list of queues which can hold this entity.
   *
   * This is copied directly from EntityQueue since there is no nice way
   * (I know of) to get this function in a block. I've opened an issue to make
   * this a service instead. If the maintainers agree and the issue
   * https://www.drupal.org/project/entityqueue/issues/3065270 is closed,
   * remove this code and replace it with the service.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface[]
   *   An array of entity queues which can hold this entity.
   */
  protected function getAvailableQueuesForEntity(NodeInterface $entity) {
    $storage = $this->entityTypeManager->getStorage('entity_queue');

    $queue_ids = $storage->getQuery()
      ->condition('entity_settings.target_type', $entity->getEntityTypeId(), '=')
      ->condition('status', TRUE)
      ->execute();

    $queues = $storage->loadMultiple($queue_ids);
    $queues = array_filter($queues, function ($queue) use ($entity) {
      /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
      $queue_settings = $queue->getEntitySettings();
      $target_bundles = &$queue_settings['handler_settings']['target_bundles'];
      return ($target_bundles === NULL || in_array($entity->bundle(), $target_bundles, TRUE));
    });

    return $queues;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }


}
