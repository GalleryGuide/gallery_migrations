<?php

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\d6\TermNode;

/**
 * Source returning tids from the term_node table for the current revision.
 *
 * @MigrateSource(
 *   id = "term_node_artists",
 *   source_provider = "taxonomy"
 * )
 */
class TermArtists extends TermNode {
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }


    return parent::prepareRow($row);
  }
}
