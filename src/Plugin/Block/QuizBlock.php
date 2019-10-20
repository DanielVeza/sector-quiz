<?php

namespace Drupal\sector_quiz\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\node\NodeInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a 'QuizBlock' block.
 *
 * @Block(
 *  id = "quiz_block",
 *  admin_label = @Translation("Quiz block"),
 * )
 */
class QuizBlock extends BlockBase implements ContainerFactoryPluginInterface, FormInterface {

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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;



  /**
   * Constructs a new QuizBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface definition.
   * @param \Symfony\Component\DependencyInjection\ContainerAwareInterface $entity_query
   *   The ContainerAwareInterface definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->formBuilder = $form_builder;
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
      $container->get('current_route_match'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $node = $this->getEntityFromRoute('node');
    if (!$node || !$node->hasField('field_scenario_answers') || empty($node->get('field_scenario_answers')->getValue())) {
      return $form;
    }

    $fieldAnswers = $node->get('field_scenario_answers')->getValue();
    dpm($node->get('field_scenario_answers')->getValue());
    $answers = [];
    foreach ($fieldAnswers as $fieldAnswer) {
      $paragraphEntity = Paragraph::load($fieldAnswer['target_id']);
      //dpm($paragraphEntity);
      // Got to be a better way of doing this...
      if (
        !empty($paragraphEntity) &&
        $paragraphEntity->hasField('field_scenario_answer') &&
        !empty($paragraphEntity->get('field_scenario_answer')->getValue()) &&
        $paragraphEntity->hasField('field_scenario_answer_correct') &&
        !empty($paragraphEntity->get('field_scenario_answer_correct')->getValue()) &&
        $paragraphEntity->hasField('field_scenario_answer_feedback') &&
        !empty($paragraphEntity->get('field_scenario_answer_feedback')->getValue())
      ) {
        //dpm($paragraphEntity->get('field_scenario_answer')->value);
        $answers[] = [
          'answer' => $paragraphEntity->get('field_scenario_answer')->value,
          'feedback' => $paragraphEntity->get('field_scenario_answer_feedback')->value,
          'correct' => $paragraphEntity->get('field_scenario_answer_correct')->value,
          'id' => $paragraphEntity->id(),
        ];
      }
    }

    //dpm([$node, $node->get('field_scenario_answers')->getValue(), $answers]);

    // Title.
    $form['calculator_title'] = [
      '#markup' => '<h3 class="calculator__title">OIA Response Calculator</h3>',
    ];
    $form['data-speed-icons'] = [
      '#title' => $this->t('Speed icons'),
      '#type' => 'radios',
      '#theme' => 'test_template',
      '#answers' => $answers,
      '#options' => [
        'animals' => $this->t('Use animal icons (rabbit and turtle)'),
        'arrows' => $this->t('Use up and down arrow icons'),
      ],
      '#ajax' => [
        'callback' => '::instrumentDropdownCallback',
        'wrapper' => 'edit-output',
        'event' => 'change',
      ],
    ];
    // Create a textbox that will be updated
    // when the user selects an item from the select box above.
    $form['container']['output'] = [
      '#type' => 'textfield',
      '#size' => '60',
      '#disabled' => TRUE,
      '#value' => 'Hello, Drupal!!1',
      '#attributes' => [
        'id' => ['edit-output'],
      ],
    ];
    // Create the submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    //dpm([$form, $form_state]);
    if (!empty($form_state->getValue('data-speed-icons'))) {
      dpm('test');
      die();
    }
    return $form;
  }

  public function instrumentDropdownCallback(array $form, FormStateInterface $form_state) {
    return  ['#markup' => '<h1>Hello</h1>'];
  }



  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = $this->formBuilder->getForm($this);
    $build['myelement'] = [
      '#theme' => 'test_template',
      '#title' => 'test-title',
    ];

    //dpm($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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

}
