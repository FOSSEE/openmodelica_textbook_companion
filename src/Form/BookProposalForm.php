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
use Drupal\textbook_companion\Services\TextbookCompanionGlobalFunction;


class BookProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    //22$page_content = "";
    if ($user->isAnonymous()) {
  // Create the error message with a link to the login page
  $msg = \Drupal::messenger()->addError(t('It is mandatory to ' . 
    \Drupal\Core\Link::fromTextAndUrl('login', \Drupal\Core\Url::fromRoute('user.page'))->toString() . 
    ' on this website to access the proposal form. If you are a new user, please create a new account first.')
  );

  // Redirect to the login page
    $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
}
    if (!$user->id()) {
  \Drupal::messenger()->addError(t('It is mandatory to login on this website to access the proposal form.'));
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
  return;
} //!$user->uid
	/* check if user has already submitted a proposal */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    //var_dump($user->id());die;
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    //var_dump($proposal_data);die;
    if ($proposal_data) {
  switch ($proposal_data->proposal_status) {
    case 0:
      $msg = \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
      break;

    case 1:
      $url = Url::fromRoute('textbook_companion.list_chapters'); // Replace 'textbook_companion.code' with your actual route name.
      $link = Link::fromTextAndUrl(t('Code Submission'), $url)->toString();
      $msg = \Drupal::messenger()->addStatus(t('Your proposal has been approved. Please go to @link to upload your code.', ['@link' => $link]));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
      break;

    case 2:
      $msg = \Drupal::messenger()->addError(t('Your proposal has been disapproved. Please create another proposal below.'));
      return $msg;
      break;

    case 3:
      $msg = \Drupal::messenger()->addStatus(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
      return $msg;
      break;

    default:
      $msg = \Drupal::messenger()->addError(t('Invalid proposal state. Please contact the site administrator for further information.'));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
      break;
  }
}
    $form = [];
    $form['imp_notice'] = [
      '#type' => 'item',
      '#markup' => '<font color="red"><b>Please fill up this form carefully as the details entered here will be exactly written in the Textbook Companion</b></font>',
    ];
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => t('Full Name'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      //'#size' => 30,
      '#value' => $user->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['mobile'] = [
      '#type' => 'textfield',
      '#title' => t('Mobile No.'),
      //'#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];
    $form['gender'] = [
      '#type' => 'radios',
      '#title' => t('Gender'),
      '#options' => [
        'M' => 'Male',
        'F' => 'Female',
      ],
      '#required' => TRUE,
    ];
    $form['how_project'] = [
      '#type' => 'select',
      '#title' => t('How did you come to know about this project'),
      '#options' => [
        'OpenModelica Website' => 'OpenModelica Website',
        'Friend' => 'Friend',
        'Professor/Teacher' => 'Professor/Teacher',
        'Mailing List' => 'Mailing List',
        'Poster in my/other college' => 'Poster in my/other college',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
    ];
    $form['course'] = [
      '#type' => 'textfield',
      '#title' => t('Course'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['branch'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' => _list_of_departments(),
      '#required' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/ Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#selected' => [
        '' => '-select-'
        ],
      '#options' => _list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => _list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      //'#size' => 30,
      '#maxlength' => 6,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#value' => 'None',
      '#title' => t('College Teacher/Professor'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['faculty_email'] = [
      '#type' => 'hidden',
      '#value' => 'None',
      '#title' => t('Teacher/Professor Email Id'),
      '#value' => '@email.com',
      //'#size' => 30,
      '#maxlength' => 50,
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#value' => 'OpenModelica TBC Team',
      '#title' => t('Reviewer'),
      //'#size' => 30,
      '#maxlength' => 50,
    ];
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('Version'),
      '#options' => _list_of_software_version(),
      '#required' => TRUE,
    ];
    $form['other_version'] = [
      '#type' => 'textfield',
      //'#size' => 30,
      '#maxlength' => 50,
      //'#required' => TRUE,
		'#description' => t('Specify the Older version used'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => [
            'value' => 'Other version'
            ]
          ]
        ],
    ];
    $form['completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#description' => t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      //'#size' => 10,
      '#maxlength' => 10,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => t('Operating System'),
      '#required' => TRUE,
      //'#size' => 30,
      '#maxlength' => 50,
    ];
    $reason = [
      'Used in more than one University' => t('Used in more than one University'),
      'The book has multiple editions' => t('The book has multiple editions'),
      'Extremely useful' => t('Extremely useful'),
      'Other reason' => t('Any other reason state below'),
    ];
    $form['reason'] = [
      '#type' => 'checkboxes',
      '#title' => t('Reasons'),
      '#options' => $reason,
      '#required' => TRUE,
    ];
    $form['other_reason'] = [
      '#type' => 'textarea',
      //'#size' => 300,
      '#maxlength' => 300,
      '#description' => t('<span style="color:red;">Maximum character limit is 255 characters</span>'),
      '#states' => [
        'visible' => [
          ':input[name="reason[Other reason]"]' => [
            'checked' => TRUE
            ]
          ]
        ],
      //'#required' => FALSE,
    ];
    $form['proposal_type'] = [
      '#type' => 'hidden',
      '#default_value' => '1',
      '#required' => FALSE,
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => t('Reference'),
      '#required' => TRUE,
      //'#size' => 500,
      '#maxlength' => 500,
      '#attributes' => [
        'placeholder' => 'Links of the syllabus must be provided....'
        ],
    ];
    $form['form_type'] = [
      '#type' => 'hidden',
      '#value' => 1,
    ];
    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => t('Book Preference'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['preference1']['book1'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the book'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => t('Author Name'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      //'#value' => $row1->author,
      //'#disabled' => ($row1->author?TRUE:FALSE),
    ];
    $form['preference1']['isbn1'] = [
      '#type' => 'textfield',
      '#title' => t('ISBN No'),
      //'#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
      // '#value' => $row1->isbn,
      // '#disabled' => ($row1->isbn?TRUE:FALSE),
    ];
    $form['preference1']['publisher1'] = [
      '#type' => 'textfield',
      '#title' => t('Publisher & Place'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      //'#value' => $row1->publisher,
    ];
    $form['preference1']['edition1'] = [
      '#type' => 'textfield',
      '#title' => t('Edition'),
      //'#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
      //'#value' => $row1->edition,
    ];
    $form['preference1']['year1'] = [
      '#type' => 'textfield',
      '#title' => t('Year of publication'),
      //'#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
      //'#value' => $row1->year,
    ];
    $form['samplefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Sample Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['samplefile']['samplefile1'] = [
      '#type' => 'file',
      '#title' => t('Upload sample source file'),
      //'#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions: ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions') . '</span>',
    ];
    $form['dir_name'] = [
      '#type' => 'hidden',
      '#value' => 'None',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    
    /* #value fix for #default_value bug drupal6  
	foreach(array("preference1", "preference2", "preference3") as $preference) {
	foreach($form[$preference] as $key => $value) {
	if(!$form[$preference][$key]["#value"]) {
	unset($form[$preference][$key]["#value"]);
	}
	}
	}*/
    return $form;
  }



  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    //var_dump("hi");die;
    $service = \Drupal::service('textbook_companion_global');
    $root_path = textbook_companion_samplecode_path();
    // @FIXME
    // // @FIXME
    // // The correct configuration object could not be determined. You'll need to
    // // rewrite this call manually.
    // $selections = variable_get("aicte_" . $user->uid, "");

    if (!$user->id()) {
      \Drupal::messenger()->addError('It is mandatory to login on this website to access the proposal form');
      return;
    } //!$user->uid
	/* completion date to timestamp */
    list($d, $m, $y) = explode('-', $form_state->getValue(['completion_date']));
    $completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
    $openmodelica_version = $form_state->getValue(['version']);
    if ($form_state->getValue(['version']) == 'Other version') {
      $form_state->setValue(['version'], $form_state->getValue(['other_version']));
    } //$form_state['values']['version'] == 'Other version'
    if ($form_state->getValue(['country']) == 'other') {
      $form_state->setValue(['country'], trim($form_state->getValue(['other_country'])));
      $form_state->setValue(['all_state'], trim($form_state->getValue(['other_state'])));
    } //$form_state['values']['country'] == 'other'
    if (isset($_POST['reason'])) {
      if (!($form_state->getValue(['other_reason']))) {
        $my_reason = implode(", ", $_POST['reason']);
      } //!($form_state['values']['other_reason'])
      else {
        $my_reason = implode(", ", $_POST['reason']);
        $my_reason = $my_reason . "-" . " " . $form_state->getValue(['other_reason']);
      }
      $form_state->setValue(['reason'], $my_reason);
    } //isset($_POST['reason'])
    // Insert data into the textbook_companion_proposal table.
$result = \Drupal::database()
  ->insert('textbook_companion_proposal')
  ->fields([
    'uid' => $user->id(), // Note: Use $user->id() instead of $user->uid in Drupal 10
    'approver_uid' => 0,
    'full_name' => trim(ucwords(strtolower($form_state->getValue('full_name')))),
    'mobile' => trim($form_state->getValue('mobile')),
    'gender' => $form_state->getValue('gender'),
    'how_project' => $form_state->getValue('how_project'),
    'course' => trim($form_state->getValue('course')),
    'branch' => $form_state->getValue('branch'),
    'university' => trim($form_state->getValue('university')),
    'city' => trim($form_state->getValue('city')),
    'pincode' => $form_state->getValue('pincode'),
    'state' => trim($form_state->getValue('all_state')),
    'country' => $form_state->getValue('country'),
    'faculty' => ucwords(strtolower($form_state->getValue('faculty'))),
    'reviewer' => ucwords(strtolower($form_state->getValue('reviewer'))),
    'reference' => trim($form_state->getValue('reference')),
    'completion_date' => $completion_date_timestamp,
    'creation_date' => time(),
    'approval_date' => 0,
    'proposal_status' => 0,
    'openmodelica_version' => trim($form_state->getValue('version')),
    'operating_system' => trim($form_state->getValue('operating_system')),
    'teacher_email' => $form_state->getValue('faculty_email'),
    'reason' => $form_state->getValue('reason'),
    'samplefilepath' => "",
    'proposal_type' => 0,
    'proposed_completion_date' => $completion_date_timestamp,
  ])
  ->execute();

    $dest_path = $result;
    //var_dump($root_path . $dest_path);die;
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        $file_type = 'S';
        if (file_exists($root_path . $dest_path . '/' . $_FILES['files']['name'][$file_form_name])) {
          // drupal_set_message(t("Error uploading file. File !filename already exists.", array('!filename' => $_FILES['files']['name'][$file_form_name])), 'error');
          unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
     // var_dump($root_path . $dest_path . $_FILES['files']['tmp_name'][$file_form_name]);die;
        else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . '/' . $_FILES['files']['name'][$file_form_name])) {
          // Update the samplefilepath for the given proposal ID.
$update_result = \Drupal::database()
  ->update('textbook_companion_proposal')
  ->fields([
    'samplefilepath' => $dest_path . '/' . $_FILES['files']['name'][$file_form_name],
  ])
  ->condition('id', $result)
  ->execute();

          \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
        }
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    if (!$result) {
      \Drupal::messenger()->addError(t('Error receiving your proposal. Please try again.'));
      return;
    } //!$result
	/* proposal id */
    $proposal_id = $result;
    /* inserting first book preference */
    if ($form_state->getValue(['book1'])) {
      /*$result = db_query("INSERT INTO {textbook_companion_preference}
		(proposal_id, pref_number, book, author, isbn, publisher, edition, year, category, approval_status) VALUES
		(%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d)",
		$proposal_id,
		1,
		ucwords(strtolower($form_state['values']['book1'])),
		ucwords(strtolower($form_state['values']['author1'])),
		$form_state['values']['isbn1'],
		ucwords(strtolower($form_state['values']['publisher1'])),
		$form_state['values']['edition1'],
		$form_state['values']['year1'],
		0,
		0
		);*/
      $bk1 = trim($form_state->getValue(['book1']));
      $auth1 = trim($form_state->getValue(['author1']));
      $pref_id = NULL;
      $directory_name = $service->_dir_name($bk1, $auth1, $pref_id);
      // Insert data into the textbook_companion_preference table.
$result = \Drupal::database()
  ->insert('textbook_companion_preference')
  ->fields([
    'proposal_id' => $proposal_id,
    'pref_number' => 1,
    'book' => trim(ucwords(strtolower($form_state->getValue('book1')))),
    'author' => trim(ucwords(strtolower($form_state->getValue('author1')))),
    'isbn' => trim($form_state->getValue('isbn1')),
    'publisher' => trim(ucwords(strtolower($form_state->getValue('publisher1')))),
    'edition' => trim($form_state->getValue('edition1')),
    'year' => trim($form_state->getValue('year1')),
    'category' => 0,
    'approval_status' => 0,
    'directory_name' => $directory_name,
  ])
  ->execute();

//      $result = \Drupal::database()->query($query, $args, $query);
      if (!$result) {
        \Drupal::messenger()->addError(t('Error receiving your first book preference.'));
      } //!$result
    } //$form_state['values']['book1']

    /* sending email */
    // $email_to = $user->mail;
    // $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
    // $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
    // $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
    // $params['proposal_received']['proposal_id'] = $proposal_id;
    // $params['proposal_received']['user_id'] = $user->uid;
    // $params['proposal_received']['headers'] = [
    //   'From' => $from,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('textbook_companion', 'proposal_received', $email_to, language_default(), $params, $from, TRUE)) {
    //   \Drupal::messenger()->addError('Error sending email message.');
    // }
    \Drupal::messenger()->addStatus(t('We have received you book proposal. We will get back to you soon.'));
    //drupal_goto('');
    $form_state->setRedirect('<front>');
  }

}
?>
