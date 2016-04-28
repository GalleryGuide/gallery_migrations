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

    print_r($fields);
    exit;
    
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    
    print_r($row);
    exit;
    
    if ($row->nid == 511) {
      exit;
    }
    
    return parent::prepareRow($row);
  }
}
