<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\GalleryTerm.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\Term;

/**
 * Custom Drupal 6 term source from database.
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
    
    $vid = $row->getSourceProperty('vid');
    $vocabulary = $this->getVocab($vid);
    $row->setSourceProperty('vid', $vocabulary);
    
    if (!empty($term_node->field_born_value)) {

      $born = array(
        array(
          'value' => $term_node->field_born_value,
        ),
      );

      $row->setSourceProperty('field_born', $born);      
    }

    if (!empty($term_node->field_born_value2)) {
      $died = array(
        array(
          'value' => $term_node->field_born_value2,
        ),
      );
      
      $row->setSourceProperty('field_died', $died);
    }

    if (!empty($term_node->field_artist_website_url)) {
      $website = array(
        array(
          'uri' => $term_node->field_artist_website_url,          
        ),
      );
      
      $row->setSourceProperty('field_website', $website);
    }

    if (!empty($term_node->body)) {
      $body = array(
        'value' => $term_node->body,
        'format' => 'filtered_html',
      );
      $row->setSourceProperty('description', $body);
    }

    return parent::prepareRow($row);
  }

  /**
   * Get the relevant node and revision IDs for a taxonomy term node.
   * 
   * @param int $tid
   *   The term ID.
   *
   * @return Object
   *   The database result row object.
   */
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

  /**
   * Get the taxonomy term node data.
   * 
   * @param Object $nid_vid
   *   The database result row object with node and revision IDs.
   * @return Object
   *   The database result row object for the taxonomy term node.
   */
  protected function getTermNode($nid_vid) {
    $nid = $nid_vid->nid;
    $vid = $nid_vid->vid;
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('content_type_artist', 'cta');
    $query->join('node_revisions', 'nr', 'cta.nid = nr.nid AND cta.vid = nr.vid');
    $query->condition('cta.nid', $nid)
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

  /**
   * Convert a D6 vocabulary ID to a D8 vocabulary name.
   * 
   * @param int $vid
   *   The D6 vocabulary ID.
   *
   * @return string
   *   The D8 vocabulary name.
   */
  protected function getVocab($vid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('vocabulary', 'v');
    $query->fields('v', array(
        'vid',
        'name',
      ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      $old_vid = $row->vid;
      
      $data[$old_vid] = strtolower($row->name);
    }
    
    return $data[$vid];
  }
}
