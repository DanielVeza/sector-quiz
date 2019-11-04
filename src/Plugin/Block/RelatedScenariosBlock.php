<?php

namespace Drupal\sector_quiz\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'RelatedScenariosBlock' block.
 *
 * @Block(
 *  id = "related_scenarios_block",
 *  admin_label = @Translation("Related scenarios block"),
 * )
 */
class RelatedScenariosBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * \Drupal\Core\Routing\RouteMatchInterface definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RelatedScenariosBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getEntityFromRoute('node');
    if (empty($node)) {
      return [];
    }
    $parentQuizValues = $node->get('field_scenario_quiz')->getValue();
    $parentQuizIds = [];
    foreach ($parentQuizValues as $key => $parentQuizValue) {
      $parentQuizIds[] = $parentQuizValue['target_id'];
    }
    $relatedScenarios = $this->entityTypeManager->getStorage('node')->getQuery()->condition('type', 'quiz_scenario')->condition('field_scenario_quiz', $parentQuizIds, 'IN')->execute();
    $scenarios = [];
    foreach ($relatedScenarios as $relatedScenario) {
      $relatedScenarioNode = Node::load($relatedScenario);
      $scenarios[] = [
        'title' => $relatedScenarioNode->label(),
        'id' => $relatedScenario,
      ];
    }
    $build = [
      '#theme' => 'sector_quiz_related_scenarios',
      '#scenarios' => $scenarios,
      '#activeId' => $node->id(),
    ];
    return $build;
  }

  /**
   * Helper function.
   *
   * Gets the current node from the route.
   *
   * Needed to get the correct node when viewing a revision.
   * Credit for this code goes to Berdir: https://www.drupal.org/u/berdir
   *
   * Taken from https://www.drupal.org/project/drupal/issues/2730631#comment-12667635
   * and adjusted slightly.
   */
  private function getEntityFromRoute($entityTypeId) {
    $entity = NULL;
    if ($this->routeMatch->getParameter($entityTypeId)) {
      $entity = $this->routeMatch->getParameter($entityTypeId);
    }
    // The node revision page does not upcast the node.
    if ($this->routeMatch->getParameter($entityTypeId . '_revision')) {
      $revision_id = $this->routeMatch->getParameter($entityTypeId . '_revision');
      if ($revision_id > 0) {
        return $this->entityTypeManager->getStorage($entityTypeId)->loadRevision($revision_id);
      }
    }
    switch ($entityTypeId) {
      case 'node':
        if ($entity instanceof NodeInterface) {
          return $entity;
        }
        break;
      case 'media':
        if ($entity instanceof MediaInterface) {
          return $entity;
        }
        break;
    }
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
