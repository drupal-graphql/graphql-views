<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Types;

use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;

/**
 * Expose views as root fields.
 *
 * @GraphQLType(
 *   id = "view_result_type",
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Types\ViewResultTypeDeriver"
 * )
 */
class ViewResultType extends TypePluginBase {

}
