<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalStatusForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;

class ProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
   // var_dump("hi");die;
    $user = \Drupal::currentUser();
    /* get current proposal */
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');

    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString());
      $response->send();
      return $msg;
    }
    $form['full_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => t('Contributor Name'),
    ];
    $form['email'] = [
      '#type' => 'item',
      '#markup' => \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['mobile'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->mobile,
      '#title' => t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_project,
      '#title' => t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->course,
      '#title' => t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->branch,
      '#title' => t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#markup' => $proposal_data->faculty,
      '#title' => t('College Teacher/Professor'),
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#markup' => $proposal_data->reviewer,
      '#title' => t('Reviewer'),
    ];
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => date('d-m-Y', $proposal_data->completion_date),
      '#title' => t('Expected Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->operating_system,
      '#title' => t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->openmodelica_version,
      '#title' => t('OpenModelica Version'),
    ];
    if ($proposal_data->proposal_type == 1) {
      $form['reason'] = [
        '#type' => 'hidden',
        '#markup' => $proposal_data->reason,
        '#title' => t('Reason'),
      ];
      $form['reference'] = [
        '#type' => 'hidden',
        '#markup' => $proposal_data->reference,
        '#title' => t('References'),
      ];
    }
    /* get book preference */
    $preference_html = '<ul>';
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
      if ($preference_data->approval_status == 1) {
        $preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')  - Approved Book</strong></li>';
      }
      else {
        $preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
      }
    }
    $preference_html .= '</ul>';
    $form['book_preference'] = [
      '#type' => 'item',
      '#markup' => $preference_html,
      '#title' => t('Book Preferences'),
    ];
    $proposal_status = '';
    switch ($proposal_data->proposal_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      case 4:
        $proposal_status = t('External');
        break;
      case 5:
        $proposal_status = t('Submitted all codes');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->proposal_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    }
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q_status = $query->execute()->fetchObject();
    if ($preference_q_status->submited_all_examples_code == 1) {
      $form['submit_all_code'] = [
        '#type' => 'checkbox',
        '#title' => t('<strong>Enable Code Submission for user</strong>'),
        '#description' => t('Check if user has not submitted all the book examples.'),
      ];
      $form['completed'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }
    else {
      if ($preference_q_status->submited_all_examples_code == 2) {

        if (($proposal_data->proposal_status == 1 || $proposal_data->proposal_status == 4) || $proposal_data->proposal_status == 5) {
          $form['completed'] = [
            '#type' => 'checkbox',
            '#title' => t('<strong>Completed</strong>'),
            '#description' => t('Check if user has completed all the book examples.'),
          ];
          $form['submit_all_code'] = [
            '#type' => 'hidden',
            '#value' => 0,
          ];
        }
      }
    }
    if ($proposal_data->proposal_status == 0) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['approve'] = array(
//             '#type' => 'item',
//             '#markup' => l('Click here', 'textbook-companion/manage-proposal/approve/' . $proposal_id),
//             '#title' => t('Approve')
//         );

      $form['completed'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
      $form['submit_all_code'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    //         '#type' => 'item',
    //         '#markup' => l(t('Cancel'), 'textbook-companion/manage-proposal/all')
    //     );

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = $form_state->getValue(['proposal_id']);
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if (!$proposal_data = $proposal_q->fetchObject()) {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString());
      $response->send();
      return $msg;
    }
    if ($form_state->getValue(['submit_all_code']) == 1) {
      /*db_query("UPDATE {textbook_companion_proposal} SET proposal_status = 3 WHERE id = %d", $proposal_id);*/
      $query = \Drupal::database()->update('textbook_companion_preference');
      $query->fields(['submited_all_examples_code' => 0]);
      $query->condition('proposal_id', $proposal_id);
      $num_updated = $query->execute();
      /* sending email */
      $book_user = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $book_user->getEmail();
      $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
      $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
      $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
      $params['all_code_submitted_status_changed']['proposal_id'] = $proposal_id;
      $params['all_code_submitted_status_changed']['user_id'] = $proposal_data->uid;
      $params['all_code_submitted_status_changed']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      // if (!drupal_mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, language_default(), $params, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE)) {
      //   \Drupal::messenger()->addError('Error sending email message.');
      // }
      \Drupal::messenger()->addStatus('User has been notified of that code submission interface is now available .');
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString());
      $response->send();
      //return $msg;
      return;
    }
    else {
      $service = \Drupal::service('textbook_companion_global');
      if ($form_state->getValue(['completed']) == 1) {
        /* set the book status to completed */
        /*db_query("UPDATE {textbook_companion_proposal} SET proposal_status = 3 WHERE id = %d", $proposal_id);*/
        $query = \Drupal::database()->update('textbook_companion_proposal');
        $query->fields([
          'proposal_status' => 3,
          'completion_date' => time(),
        ]);
        $query->condition('id', $proposal_id);
        $num_updated = $query->execute();
        $service->CreateReadmeFileTextbookCompanion($proposal_id);
        /* sending email */
        $book_user = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
        $email_to = $book_user->getEmail();
        $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
        $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
        $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
        $param['proposal_completed']['proposal_id'] = $proposal_id;
        $param['proposal_completed']['user_id'] = $proposal_data->uid;
        $param['proposal_completed']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        // if (!drupal_mail('textbook_companion', 'proposal_completed', $email_to, language_default(), $param, $from, TRUE)) {
        //   \Drupal::messenger()->addError('Error sending email message.');
        // }
        \Drupal::messenger()->addStatus('Congratulations! Book proposal has been marked as completed. User has been notified of the completion.');
      }
      else {
        \Drupal::messenger()->addError('Please select any one action.');
        $url = Url::fromUserInput('/textbook-companion/manage-proposal/status/' . $proposal_id);
$response = new RedirectResponse($url->toString());
        $response->send();
        return;
      }
    }
    $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString());
        $response->send();
    return;
  }

}
?>
