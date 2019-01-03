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
 *   provider = "views",
 *   arguments={
 *     "filter" = {
 *       "optional" = true,
 *       "type" = "Untyped"
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
 *       "type" = "Untyped"
 *     },
 *     "sortDirection" = {
 *       "optional" = true,
 *       "type" = "ViewSortDirection",
 *       "default" = "asc"
 *     },
 *     "contextualFilter" = {
 *       "optional" = true,
 *       "type" = "Untyped"
 *     }
 *   },
 *   deriver = "Drupal\graphql_core\Plugin\Deriver\Fields\EntityFieldPropertyDeriver"
 * )
 */
class ViewDerivative extends View {
  use ViewDeriverHelperTrait;


  /**
   * The overriden values from view reference field configuration.
   *
   * @var array
   */
  protected $overridenValues = [];

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
    $this->setOverridenArguments($value, $args);
    $this->setViewDefaultValues($display, $args);
    return parent::resolveValues($value, $args, $context, $info);
  }

  /**
   * Get configuration values from views reference field.
   *
   * @param mixed $value
   *   The current object value.
   */
  protected function getOverridenArguments($value) {
    $values = $value->getValue();
    $this->overridenValues = isset($values['data']) ? unserialize($values['data']) : [];
  }

  /**
   * Set default display settings.
   *
   * @param mixed $value
   *   The current object value.
   * @param array $args
   *   Arguments where the default view settings needs to be added.
   */
  protected function setOverridenArguments($value, array &$args) {
    $this->getOverridenArguments($value);
    if (isset($this->overridenValues['pager'])) {
      $this->pluginDefinition['paged'] = in_array($this->overridenValues['pager'], [
        'full',
        'mini'
      ]);
    }

    if (!isset($args['pageSize']) && isset($this->overridenValues['limit']) && !empty($this->overridenValues['limit'])) {
      $args['pageSize'] = $this->overridenValues['limit'];
    }

    if (!isset($args['offset']) && isset($this->overridenValues['offset']) && !empty($this->overridenValues['offset'])) {
      $args['offset'] = $this->overridenValues['offset'];
    }

    /* Expected format: {"contextualFilter": {"key": "value","keyN": "valueN"}} */
    if (!isset($args['contextualFilter']) && isset($this->overridenValues['argument']) && !empty($this->overridenValues['argument'])) {
      $argument = json_decode(json_encode(json_decode($this->overridenValues['argument'])), true);
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
