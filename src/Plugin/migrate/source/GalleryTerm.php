<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\GalleryTerm.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\Term;

/**
 * Custom Drupal 6 node source from database.
 *
 * @MigrateSource(
 *   id = "upgrade_d6_taxonomy_term"
 * )
 */
class GalleryTerm extends Term {
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

    // Get the related term node.
    
    /*
Fields:      
Lived	field_born	Date	Configure  Remove
Images	field_images	File	Configure  Remove 
Website	field_artist_website	Link	Configure  Remove
Body	Node module form.	  
Surname	field_surname	Computed	Configure  Remove
    
     */
    
    $tid = $row->getSourceProperty('tid');
    $term_nid_vid = $this->getTermNidVid($tid);
    $term_node = $this->getTermNode($term_nid_vid);
    
    print_r($row);
    print_r($term_node);

    return parent::prepareRow($row);
  }
  
  protected function getTermNidVid($tid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('term_node', 'tn');
    $query->condition('tid', $tid)
      ->fields('tn', array(
        'nid',
        'vid',
      ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      $data[] = $row;
    }
    return $data[0];
  }
  
  protected function getTermNode($nid_vid) {
    $nid = $nid_vid->nid;
    $vid = $nid_vid->vid;
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('content_type_artist', 'cta');
    $query->join('node_revisions', 'nr', 'cta.nid = nr.nid AND cta.vid = nr.vid');
    $query->condition('nid', $nid)
      ->condition('cta.vid', $vid)
      ->fields('cta', array(
        'field_born_value',
        'field_born_value2',
        'field_artist_website_url',
      ))
      ->fields('nr', array(
        'body',
      ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();
    
    $data = array();
    foreach ($result as $row) {
      $data[] = $row;
    }
    return $data[0];

  }
}
