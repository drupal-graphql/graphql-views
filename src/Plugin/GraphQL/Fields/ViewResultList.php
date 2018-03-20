<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Expose results of a view.
 *
 * @GraphQLField(
 *   id = "view_result",
 *   name = "results",
 *   secure = true,
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Fields\ViewResultListDeriver"
 * )
 */
class ViewResultList extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if (isset($value['rows'])) {
      foreach ($value['rows'] as $row) {
        yield $row;
      }
    }
  }

}
