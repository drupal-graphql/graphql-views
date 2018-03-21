<?php

namespace Drupal\graphql_views\Plugin\Deriver\Enums;

use Drupal\graphql\Utility\StringHelper;
use Drupal\graphql_views\Plugin\Deriver\ViewDeriverBase;
use Drupal\views\Views;

class ViewSortByDeriver extends ViewDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    if ($this->entityTypeManager->hasDefinition('view')) {
      $viewStorage = $this->entityTypeManager->getStorage('view');

      foreach (Views::getApplicableViews('graphql_display') as list($viewId, $displayId)) {
        /** @var \Drupal\views\ViewEntityInterface $view */
        $view = $viewStorage->load($viewId);
        if (!$type = $this->getRowResolveType($view, $displayId)) {
          continue;
        }

        /** @var \Drupal\graphql_views\Plugin\views\display\GraphQL $display */
        $display = $this->getViewDisplay($view, $displayId);
        $sorts = array_filter($display->getOption('sorts') ?: [], function ($sort) {
          return $sort['exposed'];
        });
        $sorts = array_reduce($sorts, function ($carry, $sort) {
          $carry[strtoupper($sort['id'])] = [
            'value' => $sort['id'],
            'description' => $sort['expose']['label'],
          ];
          return $carry;
        }, []);

        if (!empty($sorts)) {
          $id = implode('-', [$viewId, $displayId, 'view']);
          $this->derivatives["$viewId-$displayId"] = [
            'name' => StringHelper::camelCase($id, 'sort', 'by'),
            'values' => $sorts,
          ] + $basePluginDefinition;
        }
      }
    }

    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}
