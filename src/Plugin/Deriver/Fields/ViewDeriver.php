<?php

namespace Drupal\graphql_views\Plugin\Deriver\Fields;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\graphql_views\Plugin\Deriver\ViewDeriverBase;
use Drupal\views\Views;

/**
 * Derive fields from configured views.
 */
class ViewDeriver extends ViewDeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    if ($this->entityTypeManager->hasDefinition('view')) {
      $viewStorage = $this->entityTypeManager->getStorage('view');

      foreach (Views::getApplicableViews('graphql_display') as list($viewId, $displayId)) {
        /** @var \Drupal\views\ViewEntityInterface $view */
        $view = $viewStorage->load($viewId);
        if (!$this->getRowResolveType($view, $displayId)) {
          continue;
        }

        /** @var \Drupal\graphql_views\Plugin\views\display\GraphQL $display */
        $display = $this->getViewDisplay($view, $displayId);

        $id = implode('-', [$viewId, $displayId, 'view']);
        $info = $this->getArgumentsInfo($display->getOption('arguments') ?: []);
        $arguments = [];
        $arguments += $this->getContextualArguments($info, $id);
        $arguments += $this->getPagerArguments($display);
        $arguments += $this->getSortArguments($display, $id);
        $arguments += $this->getFilterArguments($display, $id);
        $types = $this->getTypes($info);

        $this->derivatives[$id] = [
          'id' => $id,
          'name' => $display->getGraphQLQueryName(),
          'type' => $display->getGraphQLResultName(),
          'parents' => $types,
          'arguments' => $arguments,
          'view' => $viewId,
          'display' => $displayId,
          'paged' => $this->isPaged($display),
          'arguments_info' => $info,
        ] + $this->getCacheMetadataDefinition($view, $display) + $basePluginDefinition;
      }
    }

    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}
