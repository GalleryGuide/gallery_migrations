<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\Gallery.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d6\Node as D6Node;

/**
 * Custom Drupal 6 node source from database.
 *
 * @MigrateSource(
 *   id = "d6_node__gallery"
 * )
 */
class Gallery extends D6Node {
  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields += array(
      'merged_body' => $this->t('Merged body'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $field1 = $row->getSourceProperty("field_field1");
    $field2 = $row->getSourceProperty("field_field2");
    $row->setDestinationProperty("merged_body", $field1 . " " . $field2);
    return parent::prepareRow($row);
  }
}
