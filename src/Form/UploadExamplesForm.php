<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BookProposalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;
use Drupal\Service;
use Drupal\textbook_companion\Services\TextbookCompanionGlobalFunction;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;




class UploadExamplesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_examples_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
$proposal_link = Link::fromTextAndUrl(
  t('proposal'),
  Url::fromUri('internal:/textbook-companion/proposal')
)->toString();

// Combine the translated string and the link
$message = t('Please submit a @proposal_link.', ['@proposal_link' => $proposal_link]);

$msg = \Drupal::messenger()->addError($message);
        $response = new RedirectResponse(Url::fromRoute('<front>')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
      }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                $msg = \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
                $response = new RedirectResponse(Url::fromRoute('<front>')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
                break;
            case 2:
                // @FIXME
// l() expects a Url object, created from a route name or external URI.
$proposal_link = Link::fromTextAndUrl(
  t('proposal'),
  Url::fromUri('internal:/textbook-companion/proposal')
)->toString();

// Combine the translated string and the link
$message = t('Your proposal has been disapproved. Please submit a new proposal @proposal_link.', ['@proposal_link' => $proposal_link]);
$msg = \Drupal::messenger->addError(t('Your proposal has been dis-approved. Please create another proposal ' . Link::fromTextAndUrl('proposal', Url::fromUri('internal:/textbook-companion/proposal'))->toString() . '.'));
$response = new RedirectResponse(Url::fromRoute('<front>')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
                break;
            case 3:
                // @FIXME
// l() expects a Url object, created from a route name or external URI.
 drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');

                drupal_goto('');
                return;
                break;
case 5:
      \Drupal::messenger()->addStatus(t('You have submitted your all codes.'));
      drupal_goto('');
      return;
            default:
                \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
                drupal_goto('');
                return;
                break;
        }
      }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
        drupal_goto('');
        return;
      }
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data"
    );
$form['book_details']['pref_id'] = array(
    '#type' => 'hidden',
    '#value' => $preference_data->id,    
  );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $options = array(
        '' => '(Select)'
    );
    for ($i = 1; $i <= 100; $i++)
      {
        $options[$i] = $i;
      }
    $form['number'] = array(
    '#type' => 'select',
    '#title' => t('Chapter No'),
    '#options' => $options,   
    '#multiple' => FALSE,
    '#size' => 1,
    '#required' => TRUE,  
    '#ajax' => array(
        'callback' => '::ajax_chapter_name_callback',
        'wrapper' => 'ajax-chapter-name-replace',
        ),
    );

    
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Title of the Chapter'),
    '#size' => 40,
    '#maxlength' => 255,
    '#required' => TRUE,    
    '#prefix' => '<div id="ajax-chapter-name-replace">',
    '#suffix' => '</div>',    
  );
    $form['example_number'] = array(
        '#type' => 'textfield',
        '#title' => t('Example No'),
        '#size' => 5,
        '#maxlength' => 10,
        '#description' => t("Example number should be separated by dots only.<br />Example: 1.1.a &nbsp;or&nbsp; 1.1.1"),
        '#required' => TRUE
    );
    $form['example_caption'] = array(
        '#type' => 'textfield',
        '#title' => t('Caption'),
        '#size' => 40,
        '#maxlength' => 255,
        '#description' => t('Example caption should contain only alphabets, numbers and spaces.'),
        '#required' => TRUE
    );
    $form['example_warning'] = array(
        '#type' => 'item',
        '#title' => t('You should upload all the files as extention ".'). \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions') . t('" main or source files, result files, executable file if any): '),
        '#prefix' => '<div style="color:red">',
        '#suffix' => '</div>'
    );
    $form['sourcefile'] = array(
        '#type' => 'fieldset',
        '#title' => t('Main or Source Files'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
    );
    $form['sourcefile']['sourcefile1'] = array(
        '#type' => 'file',
        '#title' => t('Upload main or source file'),
        '#size' => 48,
        '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions: ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions') . '</span>'
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['cancel'] = array(
//         '#type' => 'markup',
//         '#value' => l(t('Cancel'), 'textbook-companion/code')
//     );

    return $form;
  }
  public function ajax_chapter_name_callback(array &$form, FormStateInterface $form_state) {
  // Get values from form state
  $pref_id = $form_state->getValue('pref_id');
  $chapter_number = $form_state->getValue('number');

  // Query the database
  $query = \Drupal::database()->select('textbook_companion_chapter', 'tcc')
    ->fields('tcc')
    ->condition('preference_id', $pref_id)
    ->condition('number', $chapter_number);
  $result = $query->execute();
 $row = $result->fetchAll();
  // Update the form element
  if (count($row) > 0) {
    foreach($row as $chapter_data){
    $form['name']['#value'] = $chapter_data->name;
    $form['name']['#attributes']['readonly'] = 'readonly';
    $form['name']['#disabled'] = TRUE;
    }
  } else {
    $form['name']['#value'] = '';
    unset($form['name']['#attributes']['readonly']);
  }

  // Create AJAX response
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#ajax-chapter-name-replace', $form['name']));

  return $response;
}
public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // $service = \Drupal::service('textbook_companion_global');
  // var_dump($service->check_name($form_state->getValue('name']));die;
    if (!\Drupal::service('textbook_companion_global')->check_name($form_state->getValue('name')))
        $form_state->setErrorByName('name', t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    if (!\Drupal::service('textbook_companion_global')->check_name($form_state->getValue('example_caption')))
        $form_state->setErrorByName('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    if (!\Drupal::service('textbook_companion_global')->check_chapter_number($form_state->getValue('example_number')))
        $form_state->setErrorByName('example_number', t('Invalid Example Number. Example Number can contain only alphabets and numbers sepereated by dot.'));
    if (isset($_FILES['files']))
      {
        /* check if atleast one source or result file is uploaded */
        if (!($_FILES['files']['name']['sourcefile1']))
            $form_state->setErrorByName('sourcefile1', t('Please upload source file.'));
        /* check for valid filename extensions */
        foreach ($_FILES['files']['name']['sourcefile1'] as $file_form_name => $file_name)
          {
            if ($file_name)
              {
                $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions');
                $allowed_extensions = explode(',', $allowed_extensions_str);
                $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($temp_ext);
                //$temp_extension = substr($_FILES['files']['name'][$file_form_name], strripos($_FILES['files']['name'][$file_form_name], '.')); // get file name
                //var_dump($temp_extension); die;
                if (!in_array($temp_extension, $allowed_extensions))
                    $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                if ($_FILES['files']['size'][$file_form_name] <= 0)
                    $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
                // check if valid file name
                if (!$service->textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
                $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));
              }
          }
      }
  }
public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('textbook_companion_global');
    $root_path = $service->textbook_companion_path();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');

        drupal_goto('');
      }
      // var_dump($proposal_data);die;
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
                drupal_goto('');
                return;
                break;
            case 2:
                // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');

                drupal_goto('');
                return;
                break;
            case 3:
                $msg = drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
                $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
                $response->send();
                return;
                break;
            case 5:
              $msg = \Drupal::messenger()->addStatus(t('You have submmited your all codes'));
              $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
              $response->send();
              return $msg;
      break;
            default:
                $msg = \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
                $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
                $response->send();
                return $msg;
                break;
        }
      }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        $msg = \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
        $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
        $response->send();
        //drupal_goto('');
        return $msg;
      }
    /************************ end approve book details **************************/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $result = $query->execute()->fetchAll();
    if (count($result) > 1)
      {
        $msg = \Drupal::messenger()->addError(t('You cannot upload your code. This name of book directory alrady preasent in directory folder, please contact to administrator.'));
        return $msg;
      }
    $proposal_directory = $preference_data->directory_name;
    $dest_path = $proposal_directory . '/';
    if (!is_dir($root_path . $dest_path)){   
        if(!mkdir($root_path . $dest_path))
        {
        $msg = \Drupal::messenger()->addError(t('You cannot upload your code. Error in creating directory'));
        return $msg;
        }
     }   
    /* inserting chapter details */
    $chapter_id = 0;
    /*$chapter_result = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d AND number = %d", $preference_id, $form_state->getValue('number']);*/
    $preference_id = $preference_data->id;
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->condition('number', $form_state->getValue('number'));
    $chapter_result = $query->execute();
    $chapter_row = $chapter_result->fetchObject();
    if (!$chapter_row)
      {
        /*db_query("INSERT INTO {textbook_companion_chapter} (preference_id, number, name) VALUES (%d, '%s', '%s')",
        $preference_id,
        $form_state->getValue('number'],
        $form_state->getValue('name']
        );
        $chapter_id = db_last_insert_id('textbook_companion_chapter', 'id'); */
        // Insert a new record into the textbook_companion_chapter table.
// $connection = \Drupal::database();

// if ($is_new_chapter) {
//   $chapter_id = $connection->insert('textbook_companion_chapter')
//     ->fields([
//       'preference_id' => $preference_id,
//       'number' => $form_state->getValue('number'),
//       'name' => $form_state->getValue('name'),
//     ])
//     ->execute();
// }
// else {
//   $chapter_id = $chapter_row->id;

//   $connection->update('textbook_companion_chapter')
//     ->fields([
//       'name' => $form_state->getValue('name'),
//     ])
//     ->condition('id', $chapter_id)
//     ->execute();
// } 

// /*  get example details - dont allow if already example present */
//     /*$cur_example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s'", $chapter_id, $form_state->getValue('example_number']);*/
//     $query = \Drupal::database()->select('textbook_companion_example', 'tce');
// $query->fields('tce');
// // $query->condition('chapter_id', $chapter_row->id);
// $query->condition('chapter_id', $chapter_id);
// $query->condition('number', $form_state->getValue('example_number'));
// $cur_example_q = $query->execute();
// $cur_example_d = $cur_example_q->fetchObject();
// //var_dump($chapter_row->id);die;
// if ($cur_example_d) {
//   //var_dump($cur_example_d);die;
//   if ($cur_example_d->approval_status == 1) {
//     \Drupal::messenger()->addError(t("Example already approved. Cannot overwrite it."));
//     $form_state->setRedirect('textbook_companion.list_chapters');
//     return;
//   } elseif ($cur_example_d->approval_status == 0) {
//     \Drupal::messenger()->addError(t("Example is under pending review. Delete the example and reupload it."));
//     $form_state->setRedirect('textbook_companion.list_chapters');
//     return;
//   } else {
//     \Drupal::messenger()->addError(t("Error uploading example. Please contact administrator."));
//     $form_state->setRedirect('textbook_companion.list_chapters');
//     return;
//   }
// }
      $connection = \Drupal::database();

    $chapter_id = 0;
    $preference_id = $preference_data->id;

  // Now safe to use everywhere below
  $query = $connection->select('textbook_companion_chapter');
    // $query = $connection->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->condition('number', $form_state->getValue('number'));
    $chapter_result = $query->execute();
    if (!$chapter_row = $chapter_result->fetchObject()) {
      $chapter_id = $connection->insert('textbook_companion_chapter')
        ->fields([
          'preference_id' => $preference_id,
          'number' => $form_state->getValue('number'),
          'name' => $form_state->getValue('name'),
        ])
        ->execute();
    }
    else {
      $chapter_id = $chapter_row->id;
      $connection->update('textbook_companion_chapter')
        ->fields([
          'name' => $form_state->getValue('name'),
        ])
        ->condition('id', $chapter_id)
        ->execute();
    }
    $query = $connection->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('number', $form_state->getValue('example_number'));
    $cur_example_q = $query->execute();
    
    if ($cur_example_d = $cur_example_q->fetchObject()) {
      if ($cur_example_d->approval_status == 1) {
        $this->messenger()->addError($this->t('Example already approved. Cannot overwrite it.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
      elseif ($cur_example_d->approval_status == 0) {
        $this->messenger()->addError($this->t('Example is under pending review. Delete the example and reupload it.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
      else {
        $this->messenger()->addError($this->t('Error uploading example. Please contact administrator.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
    }
      
    /* creating directories */
    // $chapter_path = 'CH' . $form_state->getValue('number') . '/';
    // if (!is_dir($root_path . $dest_path))
    //     mkdir($root_path . $dest_path);
    $dest_path .= 'CH' . $form_state->getValue('number') . '/';
    if(!is_dir($root_path . $dest_path))
      mkdir($root_path . $dest_path);
    $dest_path .= 'EX' . $form_state->getValue('example_number'). '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $filepath = 'CH' . $form_state->getValue('number') . '/' . 'EX' . $form_state->getValue('example_number') . '/';
    /* creating example database entry */
    /*db_query("INSERT INTO {textbook_companion_example} (chapter_id, number, caption, approval_status, timestamp) VALUES (%d, '%s', '%s', %d, %d)",
    $chapter_id,
    $form_state->getValue('example_number'],
    $form_state->getValue('example_caption'],
    0,
    time()
    );
    $example_id = db_last_insert_id('textbook_companion_example', 'id');*/
    $example_id = \Drupal::database()
  ->insert('textbook_companion_example')
  ->fields([
    'chapter_id' => $chapter_id,
    'number' => $form_state->getValue('example_number'),
    'caption' => $form_state->getValue('example_caption'),
    'approval_date' => time(),
    'approval_status' => 0,
    'timestamp' => time(),
  ])
  ->execute();
    
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
      {
        if ($file_name)
          {
            /* checking file type */
            $file_type = 'S';
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
              {
                $msg = \Drupal::messenger()->addError(t("Error uploading file. File !filename already exists.", array(
                    '!filename' => $_FILES['files']['name'][$file_form_name]
                )));
                return $msg;
              }
            /* uploading file */
            else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
              {
                // Insert the file record into the database.
$query = \Drupal::database()->insert('textbook_companion_example_files')
  ->fields([
    'example_id' => $example_id,
    'filename' => $_FILES['files']['name'][$file_form_name],
    'filepath' => $filepath . $_FILES['files']['name'][$file_form_name],
    'filemime' => 'application/mo',
    'filesize' => $_FILES['files']['size'][$file_form_name],
    'filetype' => $file_type,
    'timestamp' => time(),
  ]);

$result = $query->execute();

// Show a success message.
\Drupal::messenger()->addStatus(t('@filename uploaded successfully.', ['@filename' => $file_name]));
              }
            else
              {
                $msg = \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
                return $msg;
              }
          }
      }
  
    $msg = \Drupal::messenger()->addStatus('Example uploaded successfully.');
	/* sending email */

// Get user email safely
$email_to = $user->getEmail();

$config = \Drupal::config('textbook_companion.settings');
$from = $config->get('textbook_companion_from_email');
$bcc = $config->get('textbook_companion_emails');
$cc = $config->get('textbook_companion_cc_emails');

$params['example_uploaded']['example_id'] = $example_id;
$params['example_uploaded']['user_id'] = $user->id();
$params['example_uploaded']['headers'] = [
  'From' => $from,
  'MIME-Version' => '1.0',
  'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
  'Content-Transfer-Encoding' => '8Bit',
  'X-Mailer' => 'Drupal',
  'Cc' => $cc,
  'Bcc' => $bcc,
];

$mailManager = \Drupal::service('plugin.manager.mail');
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

$result = $mailManager->mail(
  'textbook_companion',
  'example_uploaded',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError('Error sending email message.');
}

else {
  \Drupal::messenger()->addError('User email not found.');
}
 $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
      $response->send();
    return $msg;
	//drupal_goto('');
    
        $this->messenger()->addStatus($this->t('Example uploaded successfully.'));
}
}
}

