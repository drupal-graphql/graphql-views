<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Types;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Expose views as root fields.
 *
 * @GraphQLType(
 *   id = "view_result_type",
 *   provider = "views",
 *   unions = {"ViewResult"},
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Types\ViewResultTypeDeriver"
 * )
 */
class ViewResultType extends TypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function applies($object, ResolveContext $context, ResolveInfo $info) {
    if (isset($object['view'])) {
      /* @var \Drupal\views\Entity\View $view */
      $view = $object['view'];
      if ($this->pluginDefinition['view'] === $view->id() && $this->pluginDefinition['display'] == $view->current_display) {
        return TRUE;
      }
    }

    return parent::applies($object, $context, $info);
  }

}
