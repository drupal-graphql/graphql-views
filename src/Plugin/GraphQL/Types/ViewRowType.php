<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Types;

use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;

/**
 * Expose types for fieldable views' rows.
 *
 * @GraphQLType(
 *   id = "view_row_type",
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Types\ViewRowTypeDeriver"
 * )
 */
class ViewRowType extends TypePluginBase {

}
