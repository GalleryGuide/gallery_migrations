<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\GalleryTerm.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\migrate\source\Term as TermMigration;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Custom Drupal 6 term source from database.
 *
 * @MigrateSource(
 *   id = "upgrade_d6_taxonomy_term"
 * )
 */
class GalleryTerm extends TermMigration {
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

    // Set the vocabulary explicitly because we're not migrating them.
    $tid = $row->getSourceProperty('tid');
    $vid = $row->getSourceProperty('vid');
    $vocabulary = $this->getVocab($vid);
    $row->setSourceProperty('vid', $vocabulary);

    if ($vocabulary == 'artists') {
      // Get the related term node and import its fields.
      $term_nid = $this->getTermNid($tid);
      $term_vid = $this->getRevisionId($term_nid);
      $term_node = $this->getTermNode($term_nid, $term_vid);

      $images = $this->getArtistImages($term_nid);
      if (!empty($images)) {
        $connection = Database::getConnection();

        foreach ($images as $delta => $image) {

          $file = File::load($image['fid']);
          if (!empty($file)) {
            $image_file = \Drupal::service('image.factory')->get($file->getFileUri());

            $width = $image_file->getWidth();
            $height = $image_file->getHeight();
          }

          if (!empty($width) && !empty($height)) {

            $connection->merge('taxonomy_term__field_image')
              ->condition('entity_id', $tid)
              ->condition('deleted', 0)
              ->condition('delta', $delta)
              ->condition('langcode', 'en')
              ->fields(array(
                'bundle' => 'artist',
                'deleted' => 0,
                'entity_id' => $tid,
                'revision_id' => $tid,
                'langcode' => 'en',
                'delta' => $delta,
                'field_image_target_id' => $image['fid'],
                'field_image_alt' => !empty($image['data']['description']) ? $image['data']['description'] : '',
                'field_image_title' => !empty($image['data']['description']) ? $image['data']['description'] : '',
                'field_image_width' => $width,
                'field_image_height' => $height,
              ))
              ->execute();
          }
        }
      }

      $born = $term_node->field_born_value;
      if (!empty($born)) {
        $year_born = (int) date('Y', strtotime($born));
        $row->setSourceProperty('field_born', $year_born);
      }

      $died = $term_node->field_born_value2;
      if (!empty($died) && $died != $born) {
        $year_died = (int) date('Y', strtotime($died));
        $row->setSourceProperty('field_died', $year_died);
      }

      $url = $term_node->field_artist_website_url;
      if (!empty($url)) {
        $row->setSourceProperty('field_website', $url);
      }

      $tags = $this->getArtistTags($term_nid);
      if (!empty($tags)) {
        $this->storeArtistTags($tid, $tags);
      }

      if (!empty($term_node->body)) {
        $body = array(
          'value' => $term_node->body,
          'format' => 'filtered_html',
        );
        $row->setSourceProperty('description', $body);
      }

      $alias = $this->getArtistUrl($term_nid);
      if (!empty($alias)) {

        $node_alias = '/node/' . $term_nid;
        $term_alias = '/taxonomy/term/' . $tid;

        $connection = Database::getConnection();

        // Delete existing aliases to the term.
        $connection->delete('url_alias')
          ->condition('source', $term_alias)
          ->execute();

        // Update the term node alias to point to the term.
        $connection->update('url_alias')
          ->fields(array('source' => $term_alias))
          ->condition('source', $node_alias)
          ->execute();
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * Get the relevant node and revision IDs for a taxonomy term node.
   *
   * @param int $tid
   *   The term ID.
   *
   * @return int
   *   The node ID.
   */
  protected function getTermNid($tid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('taxonomynode', 'tn');
    $query->condition('tid', $tid)
      ->fields('tn', array(
        'nid',
      ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      $data[] = $row;
    }
    return $data[0]->nid;
  }

  /**
   * Get the current revision ID for a node.
   *
   * @param int $nid
   *   The node ID.
   * @return int
   *   The revision ID.
   */
  protected function getRevisionId($nid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('node', 'n');
    $query->condition('nid', $nid)
      ->fields('n', array(
        'vid',
      ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      $data[] = $row;
    }
    return $data[0]->vid;

  }

  /**
   * Get the taxonomy term node data.
   *
   * @param int $nid
   *   The node ID.
   * @param int $vid
   *   The relevant revision ID.
   * @return Object
   *   The database result row object for the taxonomy term node.
   */
  protected function getTermNode($nid, $vid) {
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

  /**
   * Get the alias for a taxonomy term node.
   *
   * @param int $nid
   *   The node ID.
   * @return string
   *   The alias.
   */
  protected function getArtistUrl($nid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $source_path = 'node/' . $nid;
    $query = $db->select('url_alias', 'ua');
    $query->condition('src', $source_path);
    $query->fields('ua', array(
      'dst',
    ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    foreach ($result as $row) {
      $alias = $row->dst;
    }
    return $alias;
  }

  /**
   * Get the associated images for an artist term node.
   *
   * @param int $nid
   *   The node ID.
   * @return array
   *   The file details.
   */
  protected function getArtistImages($nid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('content_field_images', 'cfi');
    $query->condition('nid', $nid);
    $query->fields('cfi', array(
      'field_images_fid',
      'field_images_data',
    ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $key => $row) {
      if (!empty($row->field_images_fid)) {
        $data[$key] = array(
          'delta' => $key,
          'fid' => $row->field_images_fid,
          'list' => 1,
          'data' => unserialize($row->field_images_data),
        );
      }
    }
    return $data;
  }

  /**
   * Get tags applied to a taxonomy node.
   *
   * @param int $nid
   *   The node ID.
   * @return array
   *   An array of terms
   */
  protected function getArtistTags($nid) {
    \Drupal\Core\Database\Database::setActiveConnection('d6');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('term_node', 'tn');
    $query->condition('nid', $nid);
    $query->fields('tn', array(
      'tid',
    ));

    $result = $query->execute();

    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      if (!empty($row->tid)) {
        $data[$row->tid] = array(
          'tid' => $row->tid,
        );
      }
    }
    return $data;
  }

  /**
   * Save tags onto an artist term.
   *
   * @param int $tid
   *   The term ID.
   *
   * @param array $tags
   *   List of term IDs to assign.
   */
  protected function storeArtistTags($tid, $tags) {

    $artist = Term::load($tid);

    foreach ($tags as $tag_tid => $tag) {
      $artist->field_tags[] = $tag_tid;
    }

    $artist->save();
  }
}
