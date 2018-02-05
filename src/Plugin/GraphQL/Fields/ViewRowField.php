<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields;

use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Expose view row fields for configured fieldable views.
 *
 * @GraphQLField(
 *   id = "view_row_field",
 *   secure = true,
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Fields\ViewRowFieldDeriver"
 * )
 */
class ViewRowField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $definition = $this->getPluginDefinition();
    if (isset($value[$definition['field']])) {
      yield $value[$definition['field']];
    }
  }
}
