<?php

namespace Drupal\graphql_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GraphQL data producer for views.
 *
 * @DataProducer(
 *   id = "views",
 *   name = @Translation("Views"),
 *   description = @Translation("Views."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "view_id" = @ContextDefinition("string",
 *       label = @Translation("View ID")
 *     ),
 *     "display_id" = @ContextDefinition("string",
 *       label = @Translation("Display ID")
 *     ),
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Offset"),
 *       required = FALSE
 *     ),
 *     "page_size" = @ContextDefinition("integer",
 *       label = @Translation("Page size"),
 *       required = FALSE
 *     ),
 *     "page" = @ContextDefinition("integer",
 *       label = @Translation("Current page"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class Views extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Resolve the data.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   * @param int|null $offset
   *   Offset of the query.
   * @param int|null $page_size
   *   Number of items on page.
   * @param int|null $page
   *   Number of page.
   *
   * @return array|null
   *   List of entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(string $view_id, string $display_id, int $offset = NULL, int $page_size = NULL, int $page = NULL) {

    /** @var \Drupal\views\Entity\View $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);

    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);

    // Set paging parameters.
    if ($this->isPaged($executable->getDisplay()) && $page_size && $page) {
      $executable->setItemsPerPage($page_size);
      $executable->setCurrentPage($page);
    }

    if ($offset) {
      $executable->setOffset($offset);
    }

    $executable->preExecute();
    $executable->execute();
    return $executable->render($display_id);
  }

  /**
   * Check if a pager is configured.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return bool
   *   Flag indicating if the view is configured with a pager.
   */
  protected function isPaged(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return isset($pagerOptions['type']) && in_array($pagerOptions['type'], [
      'full',
      'mini',
    ]);
  }

}
