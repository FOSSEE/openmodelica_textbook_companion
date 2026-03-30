<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\CodeApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;


class CodeApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'code_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    /* get a list of unapproved chapters */
    $route_match = \Drupal::routeMatch();
    $chapter_id = (int) $route_match->getParameter('chapter_id');
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $pending_chapter_q = $query->execute();
    if ($pending_chapter_data = $pending_chapter_q->fetchObject()) {
      /* get preference data */
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('id', $pending_chapter_data->preference_id);
      $result = $query->execute();
      $preference_data = $result->fetchObject();
      /* get proposal data */
      $query = \Drupal::database()->select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('id', $preference_data->proposal_id);
      $result = $query->execute();
      $proposal_data = $result->fetchObject();
    }
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid chapter selected.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.code_approval')->toString());
  $response->send();
      return $msg;
    }
    $form['#tree'] = TRUE;
    $form['contributor'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => t('Contributor Name'),
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => t('Title of the Book'),
    ];
    $form['book_details']['number'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->number,
      '#title' => t('Chapter Number'),
    ];
    $form['book_details']['name'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->name,
      '#title' => t('Title of the Chapter'),
    ];
    
    $form['book_details']['back_to_list'] = array(
            '#type' => 'link',
  '#title' => t('Back to Code Approval List'),
  '#url' => Url::fromUserInput('/textbook-companion/code-approval'),
        );

    /* get example data */
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
      $form['example_details'][$example_data->id] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => TRUE,
      ];
      $form['example_details'][$example_data->id]['example_number'] = [
        '#type' => 'item',
        '#markup' => $example_data->number,
        '#title' => t('Example Number'),
      ];
      $form['example_details'][$example_data->id]['example_caption'] = [
        '#type' => 'item',
        '#markup' => $example_data->caption,
        '#title' => t('Example Caption'),
      ];
     
      $form['example_details'][$example_data->id]['download'] = array(
        '#type' => 'link',
  '#title' => t('Download Example'),
  '#url' => Url::fromUserInput('/textbook-companion/download/example/' . $example_data->id),
              );

      $form['example_details'][$example_data->id]['approved'] = [
        '#type' => 'radios',
        '#options' => [
          'Approved',
          'Dis-approved',
        ],
      ];
      $form['example_details'][$example_data->id]['message'] = [
        '#type' => 'textfield',
        '#title' => t('Reason for dis-approval'),
      ];
      $form['example_details'][$example_data->id]['example_id'] = [
        '#type' => 'hidden',
        '#value' => $example_data->id,
      ];
      $form['example_details'][$example_data->id]['example_number_hidden'] = [
        '#type' => 'hidden',
        '#value' => $example_data->number,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    foreach ($form_state->getValue(['example_details']) as $ex_id => $ex_data) {
      if ($ex_data['approved'] == "1") {
        if ($ex_data['message'] == NULL || $ex_data['message'] == '') {
          $form_state->setErrorByName($ex_id . '][message', t('Enter reason for disapproval for experiment no: ' . $ex_data['example_number_hidden']));
        }
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    foreach ($form_state->getValue(['example_details']) as $ex_id => $ex_data) {
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('id', $ex_data['example_id']);
      $query->range(0, 1);
      $result = $query->execute();
      $example_data = $result->fetchObject();
      $query = \Drupal::database()->select('textbook_companion_chapter');
      $query->fields('textbook_companion_chapter');
      $query->condition('id', $example_data->chapter_id);
      $query->range(0, 1);
      $result = $query->execute();
      $chapter_data = $result->fetchObject();
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('id', $chapter_data->preference_id);
      $query->range(0, 1);
      $result = $query->execute();
      $preference_data = $result->fetchObject();
      $query = \Drupal::database()->select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('id', $preference_data->proposal_id);
      $query->range(0, 1);
      $result = $query->execute();
      $proposal_data = $result->fetchObject();
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      del_book_pdf($preference_data->id);
      if ($ex_data['approved'] == "0") {
        $query = \Drupal::database()->update('textbook_companion_example');
        $query->fields([
          'approval_status' => 1,
          'approver_uid' => $user->id(),
          'approval_date' => time(),
        ]);
        $query->condition('id', $ex_data['example_id']);
        $num_updated = $query->execute();
        /* sending email */
        $email_to = $user_data->getEmail();
        $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
        $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
        $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
        $param['example_approved']['example_id'] = $ex_data['example_id'];
        $param['example_approved']['user_id'] = $user_data->uid;
        $param['example_approved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        // if (!drupal_mail('textbook_companion', 'example_approved', $email_to, language_default(), $param, $from, TRUE)) {
        //   \Drupal::messenger()->addError('Error sending email message.');
        // }
      }
      else {
        if ($ex_data['approved'] == "1") {
          $service = \Drupal::service('textbook_companion_global');
          if ($service->delete_example($ex_data['example_id'])) {
            /* sending email */
            $email_to = $user_data->getEmail();
            $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
            $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
            $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
            $param['example_disapproved']['preference_id'] = $chapter_data->preference_id;
            $param['example_disapproved']['chapter_id'] = $example_data->chapter_id;
            $param['example_disapproved']['example_number'] = $example_data->number;
            $param['example_disapproved']['example_caption'] = $example_data->caption;
            $param['example_disapproved']['user_id'] = $user_data->uid;
            $param['example_disapproved']['message'] = $ex_data['message'];
            $param['example_disapproved']['headers'] = [
              'From' => $from,
              'MIME-Version' => '1.0',
              'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
              'Content-Transfer-Encoding' => '8Bit',
              'X-Mailer' => 'Drupal',
              'Cc' => $cc,
              'Bcc' => $bcc,
            ];
            // if (!drupal_mail('textbook_companion', 'example_disapproved', $email_to, language_default(), $param, $from, TRUE)) {
            //   \Drupal::messenger()->addError('Error sending email message.');
            // }
          }
          else {
            \Drupal::messenger()->addError('Error disapproving and deleting example. Please contact administrator.');
          }
        }
      }
    }
    $msg = \Drupal::messenger()->addStatus('Updated successfully.');
    //drupal_goto('textbook-companion/code-approval');
    $response = new RedirectResponse(Url::fromRoute('textbook_companion.code_approval')->toString());
  $response->send();
  return $msg;

  }

}
?>
