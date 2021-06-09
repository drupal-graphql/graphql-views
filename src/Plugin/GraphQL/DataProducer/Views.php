<?php

namespace Drupal\graphql_views\Plugin\GraphQL\DataProducer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
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
 *     ),
 *     "sort_by" = @ContextDefinition("string",
 *       label = @Translation("Sort by"),
 *       required = FALSE
 *     ),
 *     "sort_direction" = @ContextDefinition("string",
 *       label = @Translation("Sort direction"),
 *       required = FALSE
 *     ),
 *     "filter" = @ContextDefinition("any",
 *       label = @Translation("Sort direction"),
 *       required = FALSE,
 *       default_value = {}
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
   * @param string $sort_by
   *   Fields to sort by.
   * @param string $sort_direction
   *   Direction to sort. ASC or DESC.
   * @param array $filter
   *   Exposed filters.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $fieldContext
   *   Context to set cache on.
   *
   * @return array|null
   *   List of entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(string $view_id, string $display_id, $offset, $page_size, $page, $sort_by, $sort_direction, array $filter, FieldContext $fieldContext) {

    /** @var \Drupal\views\Entity\View $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);

    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);

    // Set paging parameters.
    if (empty($page_size)) {
      $page_size = $this->getPagerLimit($executable->getDisplay());
    }
    if (empty($page)) {
      $page = $this->getPagerOffset($executable->getDisplay());
    }
    if ($this->isPaged($executable->getDisplay())) {
      $executable->setItemsPerPage($page_size);
      $executable->setCurrentPage($page);
    }

    if ($offset) {
      $executable->setOffset($offset);
    }

    $available_filters = $executable->getDisplay()->getOption('filters');
    $input = $this->extractExposedInput($sort_by, $sort_direction, $filter, $available_filters);
    $executable->setExposedInput($input);

    // This is a workaround for the Taxonomy Term filter which requires a full
    // exposed form to be sent OR the display being an attachment to just
    // accept input values.
    $executable->is_attachment = TRUE;
    $executable->exposed_raw_input = $input;

    $executable->preExecute();
    $executable->execute();

    $result = $executable->render($display_id);

    /** @var \Drupal\Core\Cache\CacheableMetadata $cache */
    if ($cache = $result['cache']) {
      $cache->setCacheContexts(
        array_filter($cache->getCacheContexts(), function ($context) {
          // Don't emit the url cache contexts.
          return $context !== 'url' && strpos($context, 'url.') !== 0;
        })
      );
      $fieldContext->addCacheableDependency($cache);
    }

    return [
      'results' => $result['rows'],
      'count' => $result['view']->total_rows,
    ];
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

  /**
   * Get the configured default limit.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return int
   *   The default limit.
   */
  protected function getPagerLimit(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return NestedArray::getValue($pagerOptions, [
      'options',
      'items_per_page',
    ]) ?: 0;
  }

  /**
   * Get the configured default offset.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return int
   *   The default offset.
   */
  protected function getPagerOffset(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return NestedArray::getValue($pagerOptions, [
      'options',
      'offset',
    ]) ?: 0;
  }

  /**
   * Retrieves sort and filter arguments from the provided field args.
   *
   * @param string $sort_by
   *   Fields to sort by.
   * @param string $sort_direction
   *   Direction to sort. ASC or DESC.
   * @param array $filter
   *   Exposed filters.
   * @param array $available_filters
   *   The available filters for the configured view.
   *
   * @return array
   *   The array of sort and filter arguments to execute the view with.
   */
  protected function extractExposedInput($sort_by, $sort_direction, array $filter, array $available_filters) {
    // Prepare arguments for use as exposed form input.
    $input = array_filter([
      // Sorting arguments.
      'sort_by' => $sort_by,
      'sort_order' => $sort_direction,
    ]);

    // If some filters are missing from the input, set them to an empty string
    // explicitly. Otherwise views module generates "Undefined index" notice.
    foreach ($available_filters as $filterRow) {
      if (!isset($filterRow['expose']['identifier'])) {
        continue;
      }

      $inputKey = $filterRow['expose']['identifier'];
      if (!isset($filter[$inputKey])) {
        $input[$inputKey] = $filterRow['value'];
      }
      else {
        $input[$inputKey] = $filter[$inputKey];
      }
    }
    return $input;
  }

}
