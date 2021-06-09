<?php

namespace Drupal\graphql_views\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Provides a display plugin for GraphQL views.
 *
 * @ViewsDisplay(
 *   id = "graphql",
 *   title = @Translation("GraphQL"),
 *   help = @Translation("Creates a GraphQL entity list display."),
 *   admin = @Translation("GraphQL"),
 *   graphql_display = TRUE,
 *   returns_response = TRUE
 * )
 */
class GraphQL extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'graphql';
  }

  /**
   * {@inheritdoc}
   */
  public function usesFields() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set the default plugins to 'graphql'.
    $options['style']['contains']['type']['default'] = 'graphql';
    $options['exposed_form']['contains']['type']['default'] = 'graphql';
    $options['row']['contains']['type']['default'] = 'graphql_entity';

    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['exposed_form'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings, as they are not used for the data
    // display.
    unset($options['exposed_block']);
    unset($options['css_class']);

    $options['graphql_query_name'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    unset($categories['title']);
    unset($categories['pager'], $categories['exposed'], $categories['access']);

    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);
    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);

    unset($options['title'], $options['access']);
    unset($options['exposed_block'], $options['css_class']);
    unset($options['query'], $options['group_by']);

    $categories['graphql'] = [
      'title' => $this->t('GraphQL'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->view->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : [];

    // Apply the cache metadata from the display plugin. This comes back as a
    // cache render array so we have to transform it back afterwards.
    $this->applyDisplayCacheabilityMetadata($this->view->element);

    return [
      'view' => $this->view,
      'rows' => $rows,
      'cache' => CacheableMetadata::createFromRenderArray($this->view->element),
    ];
  }

}
