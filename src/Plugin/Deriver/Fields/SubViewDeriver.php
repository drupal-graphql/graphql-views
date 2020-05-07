<?php

namespace Drupal\graphql_views\Plugin\Deriver\Fields;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\graphql\Utility\StringHelper;
use Drupal\graphql_views\Plugin\Deriver\ViewDeriverBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derive fields from configured views.
 */
class SubViewDeriver extends ViewDeriverBase implements ContainerDeriverInterface {

  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.graphql.interface'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PluginManagerInterface $interfacePluginManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($entityTypeManager, $interfacePluginManager);
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    if ($this->entityTypeManager->hasDefinition('view')) {
      $viewStorage = $this->entityTypeManager->getStorage('view');

      foreach (Views::getApplicableViews('graphql_display') as [$viewId, $displayId]) {
        /** @var \Drupal\views\ViewEntityInterface $view */
        $view = $viewStorage->load($viewId);
        if (!$this->getRowResolveType($view, $displayId)) {
          continue;
        }

        /** @var \Drupal\graphql_views\Plugin\views\display\GraphQL $display */
        $display = $this->getViewDisplay($view, $displayId);
        $arg_options = $display->getOption('arguments');

        if (count($arg_options) == 1) {
          $arg_option = reset($arg_options);

          if (!empty($arg_option['validate']['type']) && strpos($arg_option['validate']['type'], ':') !== FALSE) {
            [$type, $entityTypeId] = explode(':', $arg_option['validate']['type']);
            if ($type == 'entity' && isset($entityTypeId)) {
              $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
              $supportsBundles = $entityType->hasKey('bundle');
              $bundles = $supportsBundles && isset($arg_option['validate_options']['bundles']) ? array_values($arg_option['validate_options']['bundles']) : [];
              $id = implode('-', [$viewId, $displayId, 'sub-view']);
              $arguments = [];
              $arguments += $this->getPagerArguments($display);
              $arguments += $this->getSortArguments($display, $id);
              $arguments += $this->getFilterArguments($display, $id);

              $parents = [];
              if (empty($bundles)) {
                $parents[] = StringHelper::camelCase($entityTypeId);

                if ($supportsBundles) {
                  $bundleInfo = array_keys($this->entityTypeBundleInfo->getBundleInfo($entityTypeId));
                  foreach ($bundleInfo as $bundle) {
                    $parents[] = StringHelper::camelCase($entityTypeId, $bundle);
                  }
                }
              }
              else {
                foreach ($bundles as $bundle) {
                  $parents[] = StringHelper::camelCase($entityTypeId, $bundle);
                }
              }

              $this->derivatives[$id] = [
                  'id' => $id,
                  'name' => $display->getGraphQLQueryName(),
                  'type' => $display->getGraphQLResultName(),
                  'parents' => $parents,
                  'arguments' => $arguments,
                  'view' => $viewId,
                  'display' => $displayId,
                  'paged' => $this->isPaged($display),
              ] + $this->getCacheMetadataDefinition($view, $display) + $basePluginDefinition;
            }
          }
        }
      }
    }

    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}
