<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Expose result count of a view.
 *
 * @GraphQLField(
 *   id = "view_result_count",
 *   name = "count",
 *   secure = true,
 *   type = "Int!",
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Fields\ViewResultCountDeriver"
 * )
 */
class ViewResultCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if (isset($value['view']) && $value['view'] instanceof ViewExecutable) {
      yield intval($value['view']->total_rows);
    }
  }

}
