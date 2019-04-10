<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields\Entity\Fields\View;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_views\Plugin\GraphQL\Fields\View;
use Drupal\graphql_views\ViewDeriverHelperTrait;
use Drupal\views\Entity\View as EntityView;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Retrieve the views field derivative.
 *
 * @GraphQLField(
 *   id = "view_derivative",
 *   secure = true,
 *   name = "viewDerivative",
 *   type = "ViewResult",
 *   field_types = {"viewsreference"},
 *   provider = "viewsreference",
 *   arguments={
 *     "filter" = {
 *       "optional" = true,
 *       "type" = "ViewsFilterInput"
 *     },
 *     "offset" = {
 *       "optional" = true,
 *       "type" = "Int"
 *     },
 *     "page" = {
 *       "optional" = true,
 *       "type" = "Int"
 *     },
 *     "pageSize" = {
 *       "optional" = true,
 *       "type" = "Int"
 *     },
 *     "sortBy" = {
 *       "optional" = true,
 *       "type" = "ViewsSortByInput"
 *     },
 *     "sortDirection" = {
 *       "optional" = true,
 *       "type" = "ViewSortDirection",
 *       "default" = "asc"
 *     },
 *     "contextualFilter" = {
 *       "optional" = true,
 *       "type" = "ViewsContextualFilterInput"
 *     }
 *   },
 *   deriver = "Drupal\graphql_core\Plugin\Deriver\Fields\EntityFieldPropertyDeriver"
 * )
 */
class ViewDerivative extends View {
  use ViewDeriverHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    $values = $value->getValue();
    $this->pluginDefinition['view'] = $values['target_id'];
    $this->pluginDefinition['display'] = $values['display_id'];
    $view = EntityView::load($values['target_id']);
    $display = $this->getViewDisplay($view, $values['display_id']);
    $this->pluginDefinition['paged'] = $this->isPaged($display);
    $this->pluginDefinition['arguments_info'] = $this->getArgumentsInfo($display->getOption('arguments') ?: []);
    $this->pluginDefinition = array_merge($this->pluginDefinition, $this->getCacheMetadataDefinition($view, $display));
    $this->setOverridenViewDefaults($value, $args);
    $this->setViewDefaultValues($display, $args);
    return parent::resolveValues($value, $args, $context, $info);
  }

  /**
   * Get configuration values from views reference field.
   *
   * @param mixed $value
   *   The current object value.
   *
   * @return array|mixed
   *   Return unserialized data.
   */
  protected function getViewReferenceConfiguration($value) {
    $values = $value->getValue();
    return isset($values['data']) ? unserialize($values['data']) : [];
  }

  /**
   * Set default display settings.
   *
   * @param mixed $value
   *   The current object value.
   * @param array $args
   *   Arguments where the default view settings needs to be added.
   */
  protected function setOverridenViewDefaults($value, array &$args) {
    $viewReferenceConfiguration = $this->getViewReferenceConfiguration($value);
    if (!empty($viewReferenceConfiguration['pager'])) {
      $this->pluginDefinition['paged'] = in_array($viewReferenceConfiguration['pager'], [
        'full',
        'mini',
      ]);
    }

    if (!isset($args['pageSize']) && !empty($viewReferenceConfiguration['limit'])) {
      $args['pageSize'] = $viewReferenceConfiguration['limit'];
    }

    if (!isset($args['offset']) && !empty($viewReferenceConfiguration['offset'])) {
      $args['offset'] = $viewReferenceConfiguration['offset'];
    }

    /* Expected format: {"contextualFilter": {"key": "value","keyN": "valueN"}} */
    if (!isset($args['contextualFilter']) && !empty($viewReferenceConfiguration['argument'])) {
      $argument = json_decode($viewReferenceConfiguration['argument'], TRUE);
      if (isset($argument['contextualFilter']) && !empty($argument['contextualFilter'])) {
        $args['contextualFilter'] = $argument['contextualFilter'];
      }
    }
  }

  /**
   * Set default display settings.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   * @param array $args
   *   Arguments where the default view settings needs to be added.
   */
  protected function setViewDefaultValues(DisplayPluginInterface $display, array &$args) {
    if (!isset($args['pageSize']) && $this->pluginDefinition['paged']) {
      $args['pageSize'] = $this->getPagerLimit($display);
    }
    if (!isset($args['page']) && $this->pluginDefinition['paged']) {
      $args['page'] = $this->getPagerOffset($display);
    }
  }

}
