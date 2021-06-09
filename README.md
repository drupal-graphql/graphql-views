# GraphQL Views for Drupal

[![Build Status](https://github.com/drupal-graphql/graphql-views/workflows/.github/workflows/test.yml/badge.svg)](https://github.com/drupal-graphql/graphql-views/actions)

[Drupal GraphQL]: https://github.com/drupal-graphql/graphql

With `graphql_views` enabled a `GraphQL` views display can be added to any view in the system.

Results can be sorted, filtered based on content fields, and relationships can be added.

Any `GraphQL` views display will provide a field that will adapt to the views configuration:

- If the view is configured with pagination, the field will accept pager arguments and return the result list and count field instead of the entity list directly.
- Any exposed filters will be added to the `filters` input type that can be used to pass filter values into the view.

Please also refer to the main [Drupal GraphQL] module for further information.
