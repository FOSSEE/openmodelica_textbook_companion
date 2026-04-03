<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\UploadExamplesAdminEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;

class UploadExamplesAdminEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_examples_admin_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('textbook_companion_global');
     $route_match = \Drupal::routeMatch();

$example_id = (int) $route_match->getParameter('example_id');
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q) {
      \Drupal::messenger()->addError(t("Invalid example selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get examples files */
    $source_file = "";
    $source_id = 0;
    $result1_file = "";
    $result1_id = 0;
    $result2_file = "";
    $result2_id = 0;
    $xcos1_file = "";
    $xcos1_id = 0;
    $xcos2_file = "";
    $xcos2_id = 0;
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    $query = \Drupal::database()->select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();
    while ($example_files_data = $example_files_q->fetchObject()) {
      if ($example_files_data->filetype == "S") {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
$source_file = Link::fromTextAndUrl(
  $example_files_data->filename,
  Url::fromUri('internal:/textbook-companion/download/file/' . $example_files_data->id))->toString();
//$source_file = l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id);

        $source_file_id = $example_files_data->id;
      }
      if ($example_files_data->filetype == "R") {
        if (strlen($result1_file) == 0) {
          // @FIXME
// l() expects a Url object, created from a route name or external URI.
 $result1_file = Link::fromTextAndUrl(
  $example_files_data->filename,
  Url::fromUri('internal:/textbook-companion/download/file/' . $example_files_data->id))->toString();

          $result1_file_id = $example_files_data->id;
        }
      }
    }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      \Drupal::messenger()->addError(t("Invalid chapter selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      \Drupal::messenger()->addError(t("Invalid book selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addError(t("Invalid proposal selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $form['#redirect'] = 'code_approval/bulk';
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => t('Title of the Book'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => t('Contributor Name'),
    ];
    $form['number'] = [
      '#type' => 'item',
      '#title' => t('Chapter No'),
      '#markup' => $chapter_data->number,
    ];
    $form['name'] = [
      '#type' => 'item',
      '#title' => t('Title of the Chapter'),
      '#markup' => $chapter_data->name,
    ];
    $form['example_number'] = [
      '#type' => 'item',
      '#title' => t('Example No'),
      '#markup' => $example_data->number,
    ];
    $form['example_caption'] = [
      '#type' => 'textfield',
      '#title' => t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $example_data->caption,
    ];
    $form['example_warning'] = [
      '#type' => 'item',
      '#title' => t('You should upload all the files (main or source files, result files, executable file if any)'),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];
    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    if ($source_file) {
      $form['sourcefile']['cur_source'] = [
        '#type' => 'item',
        '#title' => t('Existing Main or Source File'),
        '#markup' => $source_file,
      ];
      $form['sourcefile']['cur_source_checkbox'] = [
        '#type' => 'checkbox',
        '#title' => t('Delete Existing Main or Source File'),
        '#description' => 'Check to delete the existing Main or Source file.',
      ];
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file',
        '#title' => t('Upload New Main or Source File'),
        '#size' => 48,
        '#description' => t("Upload new Main or Source file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions'),
      ];
      $form['sourcefile']['cur_source_file_id'] = [
        '#type' => 'hidden',
        '#default_value' => $source_file_id,
      ];
    }
    else {
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file',
        '#title' => t('Upload New Main or Source File'),
        '#size' => 48,
        '#description' => t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions'),
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    $form['cancel'] = array(
            '#type' => 'item',
            '#markup' => Link::fromTextAndUrl(t('Cancel'),  Url::fromUri('internal:/textbook-companion/code-approval'))->toString()
        );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $service = \Drupal::service('textbook_companion_global');
    if (!$service->check_name($form_state->getValue(['example_caption']))) {
      $form_state->setErrorByName('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    }
    if (isset($_FILES['files'])) {
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          if (strstr($file_form_name, 'source')) {
            $file_type = 'S';
          }
          else {
            if (strstr($file_form_name, 'result')) {
              $file_type = 'R';
            }
            else {
              if (strstr($file_form_name, 'xcos')) {
                $file_type = 'X';
              }
              else {
                $file_type = 'U';
              }
            }
          }
          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions');
              break;
            case 'R':
              $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_result_extensions');
              break;
            case 'X':
              $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_xcos_extensions');
              break;
          }
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($temp_ext);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          /* check if valid file name */
          if (!$service->textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));
          }
        }
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('textbook_companion_global');
     $route_match = \Drupal::routeMatch();

$example_id = (int) $route_match->getParameter('example_id');
    
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q) {
      \Drupal::messenger()->addError(t("Invalid example selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    if (!$chapter_data) {
      \Drupal::messenger()->addError(t("Invalid chapter selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      \Drupal::messenger()->addError(t("Invalid book selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addError(t("Invalid proposal selected."));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
      return;
    }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    /* creating directories */
    $root_path = $service->textbook_companion_path();
    $dest_path = $preference_data->directory_name . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $dest_path .= 'CH' . $chapter_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $dest_path .= 'EX' . $example_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $filepath = 'CH' . $chapter_data->number . '/' . 'EX' . $example_data->number . '/';
    /* updating example caption */
    /*db_query("UPDATE {textbook_companion_example} SET caption = '%s' WHERE id = %d", $form_state['values']['example_caption'], $example_id);*/
    $query = \Drupal::database()->update('textbook_companion_example');
    $query->fields(['caption' => $form_state->getValue(['example_caption'])]);
    $query->condition('id', $example_id);
    $num_updated = $query->execute();
    /* handle source file */
    if (!$form_state->getValue(['cur_source_file_id'])) {
      $form_state->setValue(['cur_source_file_id'], 0);
    }
    $cur_file_id = $form_state->getValue(['cur_source_file_id']);
    if ($cur_file_id > 0) {
      /*$file_q = db_query("SELECT * FROM  {textbook_companion_example_files} WHERE id = %d AND example_id = %d", $cur_file_id, $example_data->id);
        $file_data = db_fetch_object($file_q);*/
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('id', $cur_file_id);
      $query->condition('example_id', $example_data->id);
      $result = $query->execute();
      $file_data = $result->fetchObject();
      if (!$file_data) {
        \Drupal::messenger()->addError("Error deleting example source file. File not present in database.");
        return;
      }
      if (($form_state->getValue(['cur_source_checkbox']) == 1) && (!$_FILES['files']['name']['sourcefile1'])) {
        if (!delete_file($cur_file_id)) {
          \Drupal::messenger()->addError("Error deleting example source file.");
          return;
        }
      }
    }
    if ($_FILES['files']['name']['sourcefile1']) {
      if ($cur_file_id > 0) {
        if (!delete_file($cur_file_id)) {
          \Drupal::messenger()->addError("Error removing previous example source file.");
          return;
        }
      }
      if (file_exists($root_path . $dest_path . $_FILES['files']['name']['sourcefile1'])) {
        \Drupal::messenger()->addError(t("Error uploading source file. File !filename already exists.", [
          '!filename' => $_FILES['files']['name']['sourcefile1']
          ]));
        return;
      }
      /* uploading file */
      if (move_uploaded_file($_FILES['files']['tmp_name']['sourcefile1'], $root_path . $dest_path . $_FILES['files']['name']['sourcefile1'])) {
        /* for uploaded files making an entry in the database */
        /*db_query("INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filemime, filesize, filetype, timestamp)
            VALUES (%d, '%s', '%s', '%s', %d, '%s', %d)",
            $example_data->id,
            $_FILES['files']['name']['sourcefile1'],
            $dest_path . $_FILES['files']['name']['sourcefile1'],
            $_FILES['files']['type']['sourcefile1'],
            $_FILES['files']['size']['sourcefile1'],
            'S',
            time()
            );*/
        $query = "INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filemime, filesize, filetype, timestamp)
        VALUES 	(:example_id, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
        $args = [
          ":example_id" => $example_data->id,
          ":filename" => $_FILES['files']['name']['sourcefile1'],
          ":filepath" => $filepath . $_FILES['files']['name']['sourcefile1'],
          ":filemime" => 'application/mo',
          ":filesize" => $_FILES['files']['size']['sourcefile1'],
          ":filetype" => 'S',
          ":timestamp" => time(),
        ];
        $result = \Drupal::database()->query($query, $args);
        \Drupal::messenger()->addStatus($_FILES['files']['name']['sourcefile1'] . ' uploaded successfully.');
      }
      else {
        \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $_FILES['files']['name']['sourcefile1']);
      }
    }
    /* sending email */
    $email_to = $user_data->mail;
    $param['example_updated_admin']['example_id'] = $example_id;
    $param['example_updated_admin']['user_id'] = $proposal_data->uid;
    // if (!drupal_mail('textbook_companion', 'example_updated_admin', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE)) {
    //   \Drupal::messenger()->addError('Error sending email message.');
    // }
    \Drupal::messenger()->addStatus(t("Example successfully udpated."));
  }

}
?>
