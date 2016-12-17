<?php

/**
 * @file
 * Contains \Drupal\gallery_migrations\Plugin\migrate\source\GalleryNode.
 */

namespace Drupal\gallery_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d6\Node as D6Node;

/**
 * Custom Drupal 6 node source from database.
 *
 * @MigrateSource(
 *   id = "gallery_node"
 * )
 */
class GalleryNode extends D6Node {
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
    $website = $row->getSourceProperty('field_website');
    if (!empty($website)) {
      $url = $website[0]['url'];
      $website[0]['url'] = _gallerymigrations_website_protocol($url);
      $row->setSourceProperty('field_website', $website);
    }

    // Get the location data from the D6 database.
    $nid = $row->getSourceProperty('nid');
    $location = $this->getLocation($nid);

    // Set up latitude and longitude for use with geolocation module.
    $geolocation = $this->prepareGeoLocation($location->latitude, $location->longitude);
    $row->setSourceProperty('field_location', $geolocation);

    $address = $this->prepareAddress($location);
    $row->setSourceProperty('field_address', $address);

    return parent::prepareRow($row);
  }

  /**
   * Get the location for this node from the D6 database.
   *
   * @param int $nid
   *   The node ID of the gallery.
   *
   * @return Object
   *   The database row for the location.
   */
  protected function getLocation($nid) {
    // Switch connection to access the D6 database.
    \Drupal\Core\Database\Database::setActiveConnection('migrate');
    $db = \Drupal\Core\Database\Database::getConnection();

    $query = $db->select('location_instance', 'li');
    $query->join('location', 'l', 'l.lid = li.lid');
    $query->condition('nid', $nid);
    $query->fields('l', array(
      'name',
      'street',
      'additional',
      'city',
      'province',
      'postal_code',
      'country',
      'latitude',
      'longitude',
      'source',
    ));

    $result = $query->execute();

    // Revert to the default database connection.
    \Drupal\Core\Database\Database::setActiveConnection();

    $data = array();
    foreach ($result as $row) {
      $data[] = $row;
    }

    // There should be only one row, so return that.
    return $data[0];
  }

  /**
   * Prepare geolocation data for use in D8 geolocation field.
   *
   * @param string $latitude
   * @param string $longitude
   * @return array
   *   An array to pass to the geolocation field.
   */
  protected function prepareGeoLocation($latitude, $longitude) {
    $location = array(
      array(
        'lat' => $latitude,
        'lng' => $longitude,
      ),
    );
    return $location;
  }

  /**
   * Prepare data from a location object for an address field.
   *
   * @param Object $location
   *   The location object, selected from the D6 database.
   *
   * @return array
   *   An array of values to use in an address field.
   */
  protected function prepareAddress($location) {
    return array(
      array(
        'country_code' => $this->convertCountry($location->country),
        'locality' => $location->city,
        'postal_code' => $location->postal_code,
        'address_line1' => $location->street,
        'address_line2' => $location->additional,
      ),
    );
  }

  /**
   * Convert country codes from D6 location format.
   *
   * Yes, it's hacky, but I know that the database only contains 3 values.
   *
   * @param string $country
   *   The old country code.
   *
   * @return string
   *   The ISO country code.
   */
  protected function convertCountry($country) {

    $country_codes = array(
      'uk' => 'GB',
      'pt' => 'PT',
      'nl' => 'NL',
    );

    return $country_codes[$country];
  }

}
