<?php

namespace Drupal\graphql_views;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\graphql\Utility\StringHelper;
use Drupal\graphql_views\Plugin\views\row\GraphQLEntityRow;
use Drupal\graphql_views\Plugin\views\row\GraphQLFieldRow;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\ViewEntityInterface;

/**
 * Helper functions fot view derivers.
 */
trait ViewDeriverHelperTrait {

  /**
   * Helper function to return the contextual filter argument if any exist.
   *
   * @param array $arguments
   *   The array of available arguments.
   * @param string $id
   *   The plugin derivative id.
   *
   * @return array
   *   The contextual filter argument if applicable.
   */
  protected function getContextualArguments(array $arguments, $id) {
    if (!empty($arguments)) {
      return [
        'contextualFilter' => [
          'type' => StringHelper::camelCase($id, 'contextual', 'filter', 'input'),
        ],
      ];
    }

    return [];
  }

  /**
   * Helper function to retrieve the sort arguments if any are exposed.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display plugin.
   * @param string $id
   *   The plugin derivative id.
   *
   * @return array
   *   The sort arguments if any exposed sorts are available.
   */
  protected function getSortArguments(DisplayPluginInterface $display, $id) {
    $sorts = array_filter($display->getOption('sorts') ?: [], function ($sort) {
      return $sort['exposed'];
    });
    return $sorts ? [
      'sortDirection' => [
        'type' => 'ViewSortDirection',
        'default' => 'asc',
      ],
      'sortBy' => [
        'type' => StringHelper::camelCase($id, 'sort', 'by'),
      ],
    ] : [];
  }

  /**
   * Helper function to return the filter argument if applicable.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display plugin.
   * @param string $id
   *   The plugin derivative id.
   *
   * @return array
   *   The filter argument if any exposed filters are available.
   */
  protected function getFilterArguments(DisplayPluginInterface $display, $id) {
    $filters = array_filter($display->getOption('filters') ?: [], function ($filter) {
      return array_key_exists('exposed', $filter) && $filter['exposed'];
    });

    return !empty($filters) ? [
      'filter' => [
        'type' => $display->getGraphQLFilterInputName(),
      ],
    ] : [];
  }

  /**
   * Helper function to retrieve the pager arguments if the display is paged.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display plugin.
   *
   * @return array
   *   An array of pager arguments if the view display is paged.
   */
  protected function getPagerArguments(DisplayPluginInterface $display) {
    return $this->isPaged($display) ? [
      'page' => ['type' => 'Int', 'default' => $this->getPagerOffset($display)],
      'pageSize' => [
        'type' => 'Int',
        'default' => $this->getPagerLimit($display),
      ],
    ] : [];
  }

  /**
   * Helper function to retrieve the types that the view can be attached to.
   *
   * @param array $arguments
   *   An array containing information about the available arguments.
   * @param array $types
   *   Types where it needs to be added.
   *
   * @return array
   *   An array of additional types the view can be embedded in.
   */
  protected function getTypes(array $arguments, array $types = ['Root']) {

    if (empty($arguments)) {
      return $types;
    }

    foreach ($arguments as $argument) {
      // Depending on whether bundles are known, we expose the view field
      // either on the interface (e.g. Node) or on the type (e.g. NodePage)
      // level. Here we specify types managed by other graphql_* modules,
      // yet we don't define these modules as dependencies. If types are not
      // in the schema, the resulting GraphQL field will be attached to
      // nowhere, so it won't go into the schema.
      if (empty($argument['bundles']) && empty($argument['entity_type'])) {
        continue;
      }

      if (empty($argument['bundles'])) {
        $types = array_merge($types, [StringHelper::camelCase($argument['entity_type'])]);
      }
      else {
        $types = array_merge($types, array_map(function ($bundle) use ($argument) {
          return StringHelper::camelCase($argument['entity_type'], $bundle);
        }, array_keys($argument['bundles'])));
      }
    }

    return $types;
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
   * Returns a view display object.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view object.
   * @param string $displayId
   *   The display ID to use.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayPluginInterface
   *   The view display object.
   */
  protected function getViewDisplay(ViewEntityInterface $view, $displayId) {
    $viewExecutable = $view->getExecutable();
    $viewExecutable->setDisplay($displayId);
    return $viewExecutable->getDisplay();
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
   * Check if a certain interface exists.
   *
   * @param string $interface
   *   The GraphQL interface name.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $interfacePluginManager
   *   Plugin interface manager.
   *
   * @return bool
   *   Boolean flag indicating if the interface exists.
   */
  protected function interfaceExists($interface, PluginManagerInterface $interfacePluginManager) {
    return (bool) array_filter($interfacePluginManager->getDefinitions(), function ($definition) use ($interface) {
      return $definition['name'] === $interface;
    });
  }

  /**
   * Retrieves the type the view's rows resolve to.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   * @param string $displayId
   *   The id of the current display.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $interfacePluginManager
   *   Interface plugin manager.
   *
   * @return null|string
   *   The name of the type or NULL if the type could not be derived.
   */
  protected function getRowResolveType(ViewEntityInterface $view, $displayId, PluginManagerInterface $interfacePluginManager) {
    /** @var \Drupal\graphql_views\Plugin\views\display\GraphQL $display */
    $display = $this->getViewDisplay($view, $displayId);
    $rowPlugin = $display->getPlugin('row');

    if ($rowPlugin instanceof GraphQLFieldRow) {
      return StringHelper::camelCase($display->getGraphQLRowName());
    }

    if ($rowPlugin instanceof GraphQLEntityRow) {
      $executable = $view->getExecutable();
      $executable->setDisplay($displayId);

      if ($entityType = $executable->getBaseEntityType()) {
        $typeName = $entityType->id();
        $typeNameCamel = StringHelper::camelCase($typeName);
        if ($this->interfaceExists($typeNameCamel, $interfacePluginManager)) {
          $filters = $executable->getDisplay()->getOption('filters');
          $dataTable = $entityType->getDataTable();
          $bundleKey = $entityType->getKey('bundle');

          foreach ($filters as $filter) {
            $isBundleFilter = $filter['table'] == $dataTable && $filter['field'] == $bundleKey;
            $isSingleValued = is_array($filter['value']) && count($filter['value']) == 1;
            $isExposed = isset($filter['exposed']) && $filter['exposed'];
            if ($isBundleFilter && $isSingleValued && !$isExposed) {
              $bundle = reset($filter['value']);
              $typeName .= "_$bundle";
              break;
            }
          }

          return StringHelper::camelCase($typeName);
        }
      }

      return 'Entity';
    }

    return NULL;
  }

  /**
   * Returns a view style object.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view object.
   * @param string $displayId
   *   The display ID to use.
   *
   * @return \Drupal\views\Plugin\views\style\StylePluginBase
   *   The view style object.
   */
  protected function getViewStyle(ViewEntityInterface $view, $displayId) {
    $viewExecutable = $view->getExecutable();
    $viewExecutable->setDisplay($displayId);
    return $viewExecutable->getStyle();
  }

  /**
   * Returns cache metadata plugin definitions.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The view display.
   *
   * @return array
   *   The cache metadata definitions for the plugin definition.
   */
  protected function getCacheMetadataDefinition(ViewEntityInterface $view, DisplayPluginInterface $display) {
    $metadata = $display->getCacheMetadata()
      ->addCacheTags($view->getCacheTags())
      ->addCacheContexts($view->getCacheContexts())
      ->mergeCacheMaxAge($view->getCacheMaxAge());

    return [
      'schema_cache_tags' => $metadata->getCacheTags(),
      'schema_cache_max_age' => $metadata->getCacheMaxAge(),
      'response_cache_contexts' => array_filter($metadata->getCacheContexts(), function ($context) {
        // Don't emit the url cache contexts.
        return $context !== 'url' && strpos($context, 'url.') !== 0;
      }),
    ];
  }

  /**
   * Returns information about view arguments (contextual filters).
   *
   * @param array $viewArguments
   *   The "arguments" option of a view display.
   *
   * @return array
   *   Arguments information keyed by the argument ID. Subsequent array keys:
   *     - index: argument index.
   *     - entity_type: target entity type.
   *     - bundles: target bundles (can be empty).
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getArgumentsInfo(array $viewArguments) {
    $argumentsInfo = [];
    /* @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');

    $index = 0;
    foreach ($viewArguments as $argumentId => $argument) {
      $info = [
        'index' => $index,
        'entity_type' => NULL,
        'bundles' => [],
      ];

      if (isset($argument['entity_type']) && isset($argument['entity_field'])) {
        $entityType = $entityTypeManager->getDefinition($argument['entity_type']);
        if ($entityType) {
          $idField = $entityType->getKey('id');
          if ($idField === $argument['entity_field']) {
            $info['entity_type'] = $argument['entity_type'];
            if (
              $argument['specify_validation'] &&
              strpos($argument['validate']['type'], 'entity:') === 0 &&
              !empty($argument['validate_options']['bundles'])
            ) {
              $info['bundles'] = $argument['validate_options']['bundles'];
            }
          }
        }
      }

      $argumentsInfo[$argumentId] = $info;
      $index++;
    }

    return $argumentsInfo;
  }

}
