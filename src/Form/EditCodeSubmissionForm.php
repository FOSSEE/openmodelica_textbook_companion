<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditCodeSubmissionForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class EditCodeSubmissionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_code_submission_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $preference_id = NULL) {
    /* get current proposal */
    $preference_id = arg(4);
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $preference_data = $query->execute()->fetchObject();
    if (!$preference_data) {
      \Drupal::messenger()->addError(t('Invalid book selected. Please try again.'));
      drupal_goto('textbook-companion/code-approval/edit-code-submission');
      return;
    }
    $form = [];
    $form['book'] = [
      '#type' => 'item',
      '#title' => t('Title of the book'),
      '#markup' => $preference_data->book,
    ];
    $form['author'] = [
      '#type' => 'item',
      '#title' => t('Author Name'),
      '#markup' => $preference_data->author,
    ];
    $form['isbn'] = [
      '#type' => 'item',
      '#title' => t('ISBN No'),
      '#markup' => $preference_data->isbn,
    ];
    $form['publisher'] = [
      '#type' => 'item',
      '#title' => t('Publisher & Place'),
      '#markup' => $preference_data->publisher,
    ];
    $form['edition'] = [
      '#type' => 'item',
      '#title' => t('Edition'),
      '#markup' => $preference_data->edition,
    ];
    $form['year'] = [
      '#type' => 'item',
      '#title' => t('Year of pulication'),
      '#markup' => $preference_data->year,
    ];
    $form['all_example_submitted'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable code submission interface for user'),
      '#description' => 'Once you have submited this option user can upload more examples.',
      '#required' => TRUE,
    ];
    $form['hidden_preference_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancle'] = array(
    //         '#type' => 'item',
    //         '#markup' => l('Cancle', 'textbook-companion/code-approval/edit-code-submission')
    //     );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['all_example_submitted']) != 1) {
      $form_state->setErrorByName('all_example_submitted', t('Please check the field if you are intrested to submit the all uploaded examples for review!'));
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if ($form_state->getValue(['all_example_submitted']) == 1) {
      if (\Drupal::database()->query('UPDATE textbook_companion_preference SET submited_all_examples_code = 0 WHERE id = :preference_id', [
        ':preference_id' => $form_state->getValue(['hidden_preference_id'])
        ])) {
        $query = ("SELECT proposal_id FROM textbook_companion_preference WHERE id= :preference_id");
        $args = [
          ":preference_id" => $form_state->getValue(['hidden_preference_id'])
          ];
        $proposal_data = \Drupal::database()->query($query, $args);
        $proposal_data_result = $proposal_data->fetchObject();
        $proposal_query = \Drupal::database()->select('textbook_companion_proposal');
        $proposal_query->fields('textbook_companion_proposal');
        $proposal_query->condition('proposal_status', 1);
        $proposal_query->condition('id', $proposal_data_result->proposal_id);
        $proposal_data_query = $proposal_query->execute()->fetchObject();
        /* sending email */
        $book_user = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data_query->uid);

/* sending email */
$email_to = $book_user->getEmail();   // ✅ FIX

$config = \Drupal::config('textbook_companion.settings');
$from = $config->get('textbook_companion_from_email') ?? \Drupal::config('system.site')->get('mail');
$bcc = $config->get('textbook_companion_emails');
$cc = $config->get('textbook_companion_cc_emails');

$params = [];
$params['proposal_id'] = $proposal_data_result->proposal_id;
$params['user_id'] = $user->id();
$params['cc'] = $cc;
$params['bcc'] = $bcc;

$langcode = $book_user->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();

if (!empty($email_to)) {
  $result = \Drupal::service('plugin.manager.mail')->mail(
    'textbook_companion',
    'all_code_submitted_status_changed',
    $email_to,
    $langcode,
    $params,
    $from,
    TRUE
  );

  if (empty($result['result'])) {
    \Drupal::messenger()->addError('Error sending email message.');
  }
}

\Drupal::messenger()->addMessage('Enabled code submission interface for user');

/* redirect (replacement for drupal_goto) */
$response = new RedirectResponse(
  Url::fromRoute('textbook_companion.code_approval_edit')->toString()
);
$response->send();
return;
}
    }
  }
}
?>
