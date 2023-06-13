<?php

namespace Drupal\pfe_med_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Med Connect form.
 */
class MedConnectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'med_connect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['upload_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload Excel file'),
      '#description' => $this->t('Please upload the Excel file with the required information.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validators = ['file_validate_extensions' => ['csv']];
    $file = file_save_upload('upload_file', $validators, 'public://', FILE_EXISTS_REPLACE);

    if ($file) {
      $file_path = $file->getFileUri();

      // Read and process the CSV file.
      if (($handle = fopen($file_path, 'r')) !== FALSE) {
        // Delete existing data from the table.
        pfe_med_connect_truncate_table();

        // Skip the header row.
        fgetcsv($handle);

        // Insert data into the table.
        while (($data = fgetcsv($handle)) !== FALSE) {
          $row = array_map('trim', $data);
          pfe_med_connect_insert_data($row);
        }

        fclose($handle);

        drupal_set_message($this->t('CSV file uploaded and processed successfully.'));
      }
      else {
        drupal_set_message($this->t('Error opening the CSV file.'), 'error');
      }
    }
    else {
      drupal_set_message($this->t('Error uploading the file.'), 'error');
    }
  }
}

/**
 * Helper function to truncate the custom table.
 */
function pfe_med_connect_truncate_table() {
  $table_name = 'med_connect_table';
  $query = \Drupal::database()->truncate($table_name);
  $query->execute();
}

/**
 * Helper function to insert data into the custom table.
 *
 * @param array $data
 *   The row data from the CSV file.
 */
function pfe_med_connect_insert_data(array $data) {
  $table_name = 'med_connect_table';
  $query = \Drupal::database()->insert($table_name);
  $query->fields([
    'produit' => $data[0],
    'aire_therapeutique' => $data[1],
    'departement' => $data[2],
    'rmr_email' => $data[3],
    'backup_email' => $data[4],
  ]);
  $query->execute();
}
