<?php

namespace Drupal\graphql_views\Plugin\Deriver\Fields;

use Drupal\graphql_views\Plugin\Deriver\ViewDeriverBase;
use Drupal\views\Views;

/**
 * Derive fields from configured views.
 */
class ViewResultCountDeriver extends ViewDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    if ($this->entityTypeManager->hasDefinition('view')) {
      $viewStorage = $this->entityTypeManager->getStorage('view');

      foreach (Views::getApplicableViews('graphql_display') as list($viewId, $displayId)) {
        /** @var \Drupal\views\ViewEntityInterface $view */
        $view = $viewStorage->load($viewId);
        /** @var \Drupal\graphql_views\Plugin\views\display\GraphQL $display */
        $display = $this->getViewDisplay($view, $displayId);
        if (!$this->isPaged($display)) {
          continue;
        }

        if (!$this->getRowResolveType($view, $displayId)) {
          continue;
        }

        $id = implode('-', [$viewId, $displayId, 'result', 'count']);
        $this->derivatives[$id] = [
          'id' => $id,
          'type' => 'Int',
          'parents' => [$display->getGraphQLResultName()],
          'view' => $viewId,
          'display' => $displayId,
        ] + $this->getCacheMetadataDefinition($view, $display) + $basePluginDefinition;
      }
    }

    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}
