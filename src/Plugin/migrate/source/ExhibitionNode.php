<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\ExhibitionNode.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d6\Node as D6Node;

/**
 * Custom Drupal 6 node source from database.
 *
 * @MigrateSource(
 *   id = "exhibition_node"
 * )
 */
class ExhibitionNode extends D6Node {
  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }

    // Make sure that URLs have a protocol.
    $website = $row->getSourceProperty('field_exhib_website');
    if (!empty($website)) {
      $url = $website[0]['url'];
      $website[0]['url'] = _gallerymigrations_website_protocol($url);
      $row->setSourceProperty('field_exhib_website', $website);
    }
    
    return parent::prepareRow($row);
  }
}
