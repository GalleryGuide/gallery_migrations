<?php


namespace Drupal\gallery_migrations\Plugin\migrate\process;
use Drupal\Core\Database\Database;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

class MigrateGallery extends ProcessPluginBase {
  
  protected function updateAddress($value) {
    return $value;
  }
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    print_r($row);
    
    return $this->updateAddress($value);
  }
}