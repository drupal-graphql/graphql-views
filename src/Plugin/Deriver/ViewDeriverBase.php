<?php

namespace Drupal\graphql_views\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\graphql_views\Plugin\views\row\GraphQLEntityRow;
use Drupal\graphql_views\Plugin\views\row\GraphQLFieldRow;
use Drupal\graphql\Utility\StringHelper;
use Drupal\graphql_views\ViewDeriverHelperTrait;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for graphql view derivers.
 */
abstract class ViewDeriverBase extends DeriverBase implements ContainerDeriverInterface {
  use ViewDeriverHelperTrait {
    getRowResolveType as private traitGetRowResolveType;
  }
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The interface plugin manager to search for return type candidates.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $interfacePluginManager;

  /**
   * An key value pair of data tables and the entities they belong to.
   *
   * @var string[]
   */
  protected $dataTables;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.graphql.interface')
    );
  }

  /**
   * Creates a ViewDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An entity type manager instance.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $interfacePluginManager
   *   The plugin manager for graphql interfaces.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PluginManagerInterface $interfacePluginManager
  ) {
    $this->interfacePluginManager = $interfacePluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Retrieves the entity type id of an entity by its base or data table.
   *
   * @param string $table
   *   The base or data table of an entity.
   *
   * @return string
   *   The id of the entity type that the given base table belongs to.
   */
  protected function getEntityTypeByTable($table) {
    if (!isset($this->dataTables)) {
      $this->dataTables = [];

      foreach ($this->entityTypeManager->getDefinitions() as $entityTypeId => $entityType) {
        if ($dataTable = $entityType->getDataTable()) {
          $this->dataTables[$dataTable] = $entityType->id();
        }
        if ($baseTable = $entityType->getBaseTable()) {
          $this->dataTables[$baseTable] = $entityType->id();
        }
      }
    }

    return !empty($this->dataTables[$table]) ? $this->dataTables[$table] : NULL;
  }

  /**
   * Retrieves the type the view's rows resolve to.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   * @param string $displayId
   *   Interface plugin manager.
   *
   * @return null|string
   *   The name of the type or NULL if the type could not be derived.
   */
  protected function getRowResolveType(ViewEntityInterface $view, $displayId) {
    return $this->traitGetRowResolveType($view, $displayId, $this->interfacePluginManager);
  }

}
