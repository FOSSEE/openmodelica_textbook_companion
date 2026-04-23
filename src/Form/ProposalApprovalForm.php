<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalApprovalForm.
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
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;


class ProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
//var_dump($proposal_id);die;
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    // $query = \Drupal::database()->select('textbook_companion_proposal');
    // $query->fields('textbook_companion_proposal');
    // $query->condition('proposal_status', 0);
    // $query->condition('id', $proposal_id);
    // $result = $query->execute();
    // if ($result) {
    //   if ($row = $result->fetchObject()) {
    //     /* everything ok */
    //   }
    $query = \Drupal::database()->select('textbook_companion_proposal', 'p');
$query->fields('p');

// ✅ Join users table
$query->leftJoin('users_field_data', 'u', 'u.uid = p.uid');

// ✅ Fetch email field
$query->addField('u', 'mail', 'email');

$query->condition('p.proposal_status', 0);
$query->condition('p.id', $proposal_id);

$result = $query->execute();

if ($result) {
  if ($row = $result->fetchObject()) {
    // Now $row->email is available
  }

      else {
        $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
        //drupal_goto('textbook-companion/manage-proposal');
        return $msg;
      }
    }
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
      //drupal_goto('textbook-companion/manage-proposal');
      return $msg;
    }
    //var_dump(\Drupal::entityTypeManager()->getStorage('user')->load($row->uid)->getEmail());die;
    //$row = $result->fetchObject();
    $form['full_name'] = array(
            '#type' => 'item',
            '#markup' => Link::fromTextAndUrl($row->full_name, Url::fromRoute('entity.user.canonical', ['user' => $row->uid]))->toString(),
            '#title' => t('Contributor Name')
        );

    // $form['email'] = [
    //   '#type' => 'item',
    //   '#markup' => \Drupal::entityTypeManager()->getStorage('user')->load($row->uid)->getEmail(),
    //   '#title' => t('Email'),
    // ];
$form['email_id'] = [
  '#type' => 'item',
  '#title' => $this->t('Email'),
  '#markup' => $row->email ?? 'N/A',
];
$form['mobile'] = [
      '#type' => 'item',
      '#markup' => $row->mobile,
      '#title' => t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#markup' => $row->how_project,
      '#title' => t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#markup' => $row->course,
      '#title' => t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#markup' => $row->branch,
      '#title' => t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $row->university,
      '#title' => t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $row->city,
      '#title' => t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $row->pincode,
      '#title' => t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $row->state,
      '#title' => t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#markup' => $row->faculty,
      '#title' => t('College Teacher/Professor'),
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#markup' => $row->reviewer,
      '#title' => t('Reviewer'),
    ];
    if ($row->proposed_completion_date != 0) {
      $proposed_completion_date = date('d-m-Y', $row->proposed_completion_date);
    }
    else {
      $proposed_completion_date = "-----";
    }

    $form['proposed_completion_date'] = [
      '#type' => 'item',
      '#markup' => $proposed_completion_date,
      '#title' => t('Proposed Date of Completion'),
    ];
    if ($row->completion_date != 0) {
      $actual_completion_date = date('d-m-Y', $row->completion_date);
    }
    else {
      $actual_completion_date = "-----";
    }
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => $actual_completion_date,
      '#title' => t('Actual Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $row->operating_system,
      '#title' => t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $row->openmodelica_version,
      '#title' => t('OpenModelica Version'),
    ];
    $form['reference'] = [
      '#type' => 'item',
      '#markup' => $row->reference,
      '#title' => t('References'),
    ];
    $form['reason'] = [
      '#type' => 'item',
      '#markup' => $row->reason,
      '#title' => t('Reasons'),
    ];
    /* get book preference */
    $preference_rows = [];
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
      $preference_rows[$preference_data->id] = $preference_data->book . ' (Written by ' . $preference_data->author . ')';
    }
    if ($row->proposal_type == 1) {
      $form['book_preference'] = [
        '#type' => 'radios',
        '#options' => $preference_rows,
        '#title' => t('Book Preferences'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['book_preference'] = [
        '#type' => 'radios',
        '#title' => t('Book Preferences'),
        '#options' => $preference_rows,
        '#required' => TRUE,
      ];
    }
    if ($row->samplefilepath != "Not available") {
    $form['samplecode'] = array(
                '#type' => 'markup',
                '#title' => 'Samplecode uploaded by contributor',
                '#markup' => Link::fromTextAndUrl('Download Sample Code', Url::fromUri('internal:/textbook-companion/download/samplecode/' . $row->id))->toString(),
            );

    }
    $form['disapprove'] = [
      '#type' => 'checkbox',
      '#title' => t('Disapprove all the above book preferences'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#states' => [
        'visible' => [
          ':input[name="disapprove"]' => [
            'checked' => TRUE
            ]
          ],
        'required' => [':input[name="disapprove"]' => ['checked' => TRUE]],
      ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    
    $form['cancel'] = array(
            '#type' => 'markup',
            '#markup' => Link::fromTextAndUrl('Cancel', Url::fromUri('internal:/textbook-companion/manage-proposal'))->toString()
            // '#value' => l(t('Cancel'), 'textbook-companion/manage-proposal')
        );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['disapprove'])) {
      if (strlen(trim($form_state->getValue(['message']))) <= 30) {
        $form_state->setErrorByName('message', t('Please mention the reason for disapproval in minimum 30 characters.'));
      }
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = $form_state->getValue(['proposal_id']);
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    // var_dump($proposal_id);die;
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
        //drupal_goto('textbook-companion/manage-proposal');
        return $msg;
      }
    }
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
      //drupal_goto('textbook-companion/manage-proposal');
      return $msg;
    }
    /* disapprove */
    if ($form_state->getValue(['disapprove'])) {
      /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 2, message = '%s' WHERE id = %d", $user->uid, time(), $form_state['values']['message'], $proposal_id);*/
      $query = \Drupal::database()->update('textbook_companion_proposal');
      $query->fields([
        'approver_uid' => $user->id(),
        'approval_date' => time(),
        'proposal_status' => 2,
        'completion_date' => '0',
        'message' => $form_state->getValue(['message']),
      ]);
      $query->condition('id', $proposal_id);
      $num_updated = $query->execute();
      /*db_query("UPDATE {textbook_companion_preference} SET approval_status = 2 WHERE proposal_id = %d", $proposal_id);*/
      $query = \Drupal::database()->update('textbook_companion_preference');
      $query->fields(['approval_status' => 2]);
      $query->condition('proposal_id', $proposal_id);
      $num_updated = $query->execute();
      /* unlock all the aicte books */
      /*$query = "
        UPDATE textbook_companion_aicte
        SET status = 0, uid = 0, proposal_id = 0, preference_id = 0
        WHERE proposal_id = {$proposal_id}
        ";
        db_query($query);*/
      /*$query = db_update('textbook_companion_aicte');
        $query->fields(array(
        'status' => 0,
        'uid' => 0,
        'proposal_id' => 0,
        'preference_id' => 0,
        ));
        $query->condition('proposal_id', $proposal_id);
        $num_updated = $query->execute();*/
      /* sending email */
      // $book_user = \Drupal::entityTypeManager()->getStorage('user')->load($row->uid);

// Load user safely
$user_storage = \Drupal::entityTypeManager()->getStorage('user');
$book_user = $user_storage->load($row->uid);

$email_to = $book_user ? $book_user->getEmail() : NULL;

// Config values
$config = \Drupal::config('textbook_companion.settings');
$from = $config->get('textbook_companion_from_email') ?: \Drupal::config('system.site')->get('mail');
$bcc = $config->get('textbook_companion_emails');
$cc = $config->get('textbook_companion_cc_emails');

// Params
$params = [];
$params['proposal_disapproved'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $row->uid,
  'headers' => [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal',
    'Cc' => $cc,
    'Bcc' => $bcc,
  ],
];

// Send mail
if (!empty($email_to)) {
  $mailManager = \Drupal::service('plugin.manager.mail');

  $result = $mailManager->mail(
    'textbook_companion',               // module
    'proposal_disapproved',             // key
    $email_to,                          // to
    \Drupal::currentUser()->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );

  if (empty($result['result'])) {
    \Drupal::messenger()->addMessage(' Sending email message.');
  }
}
else {
  \Drupal::messenger()->addError('User email not found.');
}    

$msg = \Drupal::messenger()->addError('Book proposal dis-approved. User has been notified of the dis-approval.');
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
      return $msg;
    }
    /* get book preference and set the status */
    $preference_id = $form_state->getValue(['book_preference']);
    /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 1 WHERE id = %d", $user->uid, time(), $proposal_id);*/
    $query = \Drupal::database()->update('textbook_companion_proposal');
    $query->fields([
      'approver_uid' => $user->id(),
      'approval_date' => time(),
      'proposal_status' => 1,
    ]);
    $query->condition('id', $proposal_id);
    $num_updated = $query->execute();
    /*db_query("UPDATE {textbook_companion_preference} SET approval_status = 1 WHERE id = %d", $preference_id);*/
    $query = \Drupal::database()->update('textbook_companion_preference');
    $query->fields(['approval_status' => 1]);
    $query->condition('id', $preference_id);
    $num_updated = $query->execute();
    /* unlock aicte books except the one which was approved out of 3 nos */
    /* $query = "
    UPDATE textbook_companion_aicte
    SET status = 0, uid = 0, proposal_id = 0, preference_id = 0
    WHERE proposal_id = {$proposal_id} AND preference_id != {$preference_id}
    ";
    db_query($query);*/
    /*$query = db_update('textbook_companion_aicte');
    $query->fields(array(
    'status' => 0,
    'uid' => 0,
    'proposal_id' => 0,
    'preference_id' => 0,
    ));
    $query->condition('proposal_id', '$proposal_id');
    $query->condition('preference_id', '$preference_id', '<>');
    $num_updated = $query->execute();*/
    /* sending email */

// Load user safely
$user_storage = \Drupal::entityTypeManager()->getStorage('user');
$book_user = $user_storage->load($row->uid);

$email_to = $book_user ? $book_user->getEmail() : NULL;

// Config values
$config = \Drupal::config('textbook_companion.settings');
$from = $config->get('textbook_companion_from_email') ?: \Drupal::config('system.site')->get('mail');
$bcc = $config->get('textbook_companion_emails');
$cc = $config->get('textbook_companion_cc_emails');

// Params
$params = [];
$params['proposal_approved'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $row->uid,
  'headers' => [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal',
    'Cc' => $cc,
    'Bcc' => $bcc,
  ],
];

// Send mail
if (!empty($email_to)) {

  $mailManager = \Drupal::service('plugin.manager.mail');

  $result = $mailManager->mail(
    'textbook_companion',               // module name
    'proposal_approved',                // key
    $email_to,                          // recipient
    \Drupal::currentUser()->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );

  if (empty($result['result'])) {
    \Drupal::messenger()->addError('Error sending email message.');
  }
}
else {
  \Drupal::messenger()->addError('User email not found.');
}

    $msg = \Drupal::messenger()->addStatus('Book proposal approved. User has been notified of the approval');
    $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
    return $msg;
  }

}
?>
