<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields\Entity\Fields\View;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_views\Plugin\GraphQL\Fields\View;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Retrieve the image field derivative (image style).
 *
 * @GraphQLField(
 *   id = "view_derivative",
 *   secure = true,
 *   name = "viewDerivative",
 *   type = "View",
 *   field_types = {"viewsreference"},
 *   provider = "views",
 *   deriver = "Drupal\graphql_core\Plugin\Deriver\Fields\EntityFieldPropertyDeriver"
 * )
 */
class ViewDerivative extends View {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    $values = $value->getValue();
    $this->pluginDefinition['view'] = $values['target_id'];
    $this->pluginDefinition['display'] = $values['display_id'];
    // TODO: add support for arguments and paged?
    // $this->pluginDefinition['arguments_info']
    // $this->pluginDefinition['paged']
    return parent::resolveValues($value, $args, $context, $info);
  }

}
