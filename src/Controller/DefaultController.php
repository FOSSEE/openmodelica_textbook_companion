<?php /**
 * @file
 * Contains \Drupal\textbook_companion\Controller\DefaultController.
 */

namespace Drupal\textbook_companion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
/**
 * Default controller for the textbook_companion module.
 */
class DefaultController extends ControllerBase {

  public function textbook_companion_aicte_proposal_all() {
    $user = \Drupal::currentUser();
    $page_content = "";
    if (!$user->id()) {
      /*$query = "
		SELECT * FROM textbook_companion_aicte
		WHERE status = 0
		";
		$result = db_query($query);*/
      $query = \Drupal::database()->select('textbook_companion_aicte');
      $query->fields('textbook_companion_aicte');
      $query->condition('status', 0);
      $result = $query->execute();
      $page_content .= "<ul>";
      $page_content .= "<li>These are the list of books available for <em>Textbook Companion</em> proposal.</li>";
      $page_content .= "<li>Please <a href='/user'><b><u>Login</u></b></a> to create a proposal.</li>";
      $page_content .= "</ul>";
      $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
      $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
      $page_content .= "<div id='aicte-list-wrapper'>";
      $num_rows = $result->rowCount();
      if ($num_rows > 0) {
        $i = 1;
        while ($row = $result->fetchObject()) {
          /* fixing title string */
          $title = "";
          $edition = "";
          $year = "";
          $title = "{$row->book} by {$row->author}";
          if ($row->edition) {
            $edition = "<i>ed</i>: {$row->edition}";
          } //$row->edition
          if ($row->year) {
            if ($row->edition) {
              $year = ", <i>pub</i>: {$row->year}";
            } //$row->edition
            else {
              $year = "<i>pub</i>: {$row->year}";
            }
          } //$row->year
          if ($edition or $year) {
            $title .= "({$edition} {$year})";
          } //$edition or $year
          $page_content .= "<div class='title'>{$i}) {$title}</div>";
          $i++;
        } //$row = $result->fetchObject()
      } //$num_rows > 0
      $page_content .= "</div>";
      /* adding aicte report form */
      //$page_content .= drupal_get_form("textbook_companion_aicte_report_form");
      return $page_content;
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /* $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
            drupal_goto('');
            return;
            break;
          case 1:
            // @FIXME
            // l() expects a Url object, created from a route name or external URI.
            // drupal_set_message(t('Your proposal has been approved. Please go to ' . l('Code Submission', 'textbook-companion/code') . ' to upload your code'), 'status');

            drupal_goto('');
            return;
            break;
          case 2:
            \Drupal::messenger()->addError(t('Your proposal has been dis-approved. Please create another proposal below.'));
            break;
          case 3:
            \Drupal::messenger()->addStatus(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
            break;
          default:
            \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
            drupal_goto('');
            return;
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    // @FIXME
    // // @FIXME
    // // The correct configuration object could not be determined. You'll need to
    // // rewrite this call manually.
    // variable_del("aicte_" . $user->uid);

    $page_content .= "<h5><b>* Please select any 3 books from the below list.</b></h5></br>";
    $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
    $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
    //$page_content .= drupal_get_form("textbook_companion_aicte_report_form");
    $textbook_companion_aicte_proposal_form = \Drupal::formBuilder()->getForm("textbook_companion_aicte_proposal_form");
    $page_content .= \Drupal::service("renderer")->render($textbook_companion_aicte_proposal_form);
    return $page_content;
  }
public function textbook_companion_completed_books() {
    $output = '';

    $database = \Drupal::database();
    $query = $database->select('textbook_companion_preference', 'pe');
    $query->fields('pe', ['book', 'author', 'publisher', 'year', 'id']);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->fields('po', ['full_name', 'university', 'completion_date']);
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->orderBy('po.completion_date', 'DESC');

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
        $output .= "Work has been completed on the following books under the Textbook Companion Project. 
                    <span style='color:red;'>The list below is not the books as named but only are the solved example for CFD</span>";
    }
    else {
        $output .= "Work has been completed on the following books under the Textbook Companion Project. <br>
                    <span style='color:red;'>The list below is not the books as named but only are the solved example for CFD.</span>";

        $rows = [];
        $i = count($results);
        foreach ($results as $row) {
            $completion_year = date("Y", $row->completion_date);

            $link = Link::fromTextAndUrl(
                $row->book . ' by ' . $row->author . ', ' . $row->publisher . ', ' . $row->year,
                Url::fromUserInput('/textbook-companion/textbook-run/' . $row->id)
            )->toString();

            $rows[] = [
                $i,
                $link,
                $row->full_name,
                $row->university,
                $completion_year,
            ];
            $i--;
        }

        $header = [
            'No',
            'Title of the Book',
            'Contributor Name',
            'University / Institute',
            'Year of Completion'
        ];

            $output =  [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      //'#empty' => 'no rows found',
    ];    }

    return $output;
}
  public function _proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    /*$pending_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 ORDER BY id DESC");*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      //var_dump($pending_data);die;

      $approve_link = Link::fromTextAndUrl(t('Approve'), 
      Url::fromRoute('textbook_companion.proposal_approval_form', ['id' => $pending_data->id]))->toString();

// Edit link
$edit_link = Link::fromTextAndUrl(t('Edit'),
  Url::fromRoute('textbook_companion.proposal_edit_form', ['id' => $pending_data->id]))->toString();

// Combine the links with a separator
$mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approve_link, '@linkReject' => $edit_link));
$pending_rows[] = array(
            date('d-m-Y', $pending_data->creation_date),
            Link::fromTextAndUrl($pending_data->full_name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
            date('d-m-Y', $pending_data->proposed_completion_date),
            $mainLink
            //l('Approve', 'textbook-companion/manage-proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $pending_data->id)
        );

    }
    /* check if there are any pending proposals */
    if (!$pending_rows) {
      \Drupal::messenger()->addStatus(t('There are no pending proposals.'));
      return '';
    }
    $pending_header = [
      'Date of Submission',
      'Contributor Name',
      'Proposed Date of Completion',
      'Action',
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    $output = [
  '#theme' => 'table',
  '#header' => $pending_header,
  '#rows' => $pending_rows,
  '#empty' => t('No pending proposals found.'),
];

    return $output;
  }

  public function _proposal_all() {
    
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC");*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      /* get preference */
      /*$preference_q = db_query("SELECT * FROM textbook_companion_preference WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);   
        $preference_data = db_fetch_object($preference_q);*/
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('proposal_id', $proposal_data->id);
      $query->condition('approval_status', 1);
      $query->range(0, 1);
      $preference_q = $query->execute();
      $preference_data = $preference_q->fetchObject();
      if (!$preference_data) {
        /* $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = 1 LIMIT 1", $proposal_data->id);  
            $preference_data = db_fetch_object($preference_q);*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('pref_number', 1);
        //$query->condition('approval_status', 0);        
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
      }
      $proposal_status = '';
      switch ($proposal_data->proposal_status) {
        case 0:
          $proposal_status = 'Pending';
          break;
        case 1:
          $proposal_status = 'Approved';
          break;
        case 2:
          $proposal_status = 'Dis-approved';
          break;
        case 3:
          $proposal_status = 'Completed';
          break;
        case 4:
          $proposal_status = 'External';
          break;
        case 5:
          $proposal_status = 'Submitted all codes';
          break;
        default:
          $proposal_status = 'Unknown';
          break;
      }
      if ($proposal_data->proposed_completion_date != 0) {
        $proposed_completion_date = date('d-m-Y', $proposal_data->proposed_completion_date);
      }
      else {
        $proposed_completion_date = "-----";
      }
      
      $status_link = Link::fromTextAndUrl(t('Status'),Url::fromUri('internal:/textbook-companion/manage-proposal/status/' . $proposal_data->id))->toString();

// Edit link
$edit_link = Link::fromTextAndUrl(t('Edit'),
  Url::fromRoute('textbook_companion.proposal_edit_form', ['id' => $proposal_data->id]))->toString();

// Combine the links with a separator
$mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $status_link, '@linkReject' => $edit_link));
      $proposal_rows[] = array(
                  date('d-m-Y', $proposal_data->creation_date),
                  $preference_data->book . ' by ' . $preference_data->author,
                 Link::fromTextAndUrl($pending_data->full_name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),
                  date('d-m-Y', $proposal_data->completion_date),
                  $proposed_completion_date,
                  $proposal_status,
                  $mainLink
                  //l('Status', 'textbook-companion/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $proposal_data->id) . _tbc_ext($proposal_status, $preference_data->id)
              );

    }
    /* check if there are any pending proposals */
    // if (!$proposal_rows) {
    //   \Drupal::messenger()->addStatus(t('There are no proposals.'));
    //   return '';
    // }
    $proposal_header = [
      'Date of Submission',
      'Title of the Book',
      'Contributor Name',
      'Actual Date of Completion',
      'Proposed Date of Completion',
      'Status',
      'Action',
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $output = theme('table', array(
    //         'header' => $proposal_header,
    //         'rows' => $proposal_rows
    //     ));
$output = [
  '#theme' => 'table',
  '#header' => $proposal_header,
  '#rows' => $proposal_rows,
  '#empty' => t('No proposals found.'),
];
    return $output;
  }

  public function _failed_all($preference_id = 0, $confirm = "") {
    $page_content = "";
    if ($preference_id && $confirm == "yes") {
      /*$query = "
        SELECT *, pro.id as proposal_id FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
      $query = \Drupal::database()->select('textbook_companion_proposal', 'pro');
      $query->fields('*', ['']);
      $query->fields('pro', ['id']);
      $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
      $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
      $query->condition('pre.id', '$preference_id');
      $result = $query->execute();
      $row = $result->fetchObject();
      /* increment failed_reminder */
      /*$query = "
        UPDATE textbook_companion_proposal
        SET failed_reminder = failed_reminder + 1
        WHERE id = {$row->proposal_id}
        ";
        db_query($query);*/
      $query = \Drupal::database()->update('textbook_companion_proposal');
      $query->fields(['failed_reminder' => 'failed_reminder + 1']);
      $query->condition('id', '$row->proposal_id');
      $num_updated = $query->execute();
      /* sending mail */
      $to = $row->mail;
      $subject = "Failed to upload the TBC codes on time";
      $body = "
    <p>
      Dear {$row->name},<br><br>
      This is to inform you that you have failed to upload the TBC codes on time.<br>
      Please note that the time you have taken is way past the deadline as well.<br>
      Kindly upload the TBC codes on the interface within 5 days from now.<br>
      Failure to submit the same will result in disapproval of your work and cancellation of your internship.<br><br>
      Regards,<br>
      OpenModelica TBC Team,<br>
      FOSSEE.
    </p>
    ";
      $message = [
        "to" => $to,
        "subject" => $subject,
        "body" => $body,
        "headers" => [
          "From" => "contact-openmodelica@fossee.in",
          "Bcc" => "contact-openmodelica@fossee.in",
          "Content-Type" => "text/html; charset=UTF-8; format=flowed",
        ],
      ];
      drupal_mail_send($message);
      \Drupal::messenger()->addMessage("Reminder sent successfully.");
      drupal_goto("textbook-companion/manage-proposal/failed");
    }
    else {
      if ($preference_id) {
        /*$query = "
        SELECT * FROM textbook_companion_preference pre
        LEFT JOIN textbook_companion_proposal pro ON pro.id = pre.proposal_id
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
        $query = \Drupal::database()->select('textbook_companion_preference', 'pre');
        $query->fields('pre');
        $query->leftJoin('textbook_companion_proposal', 'pro', 'pro.id = pre.proposal_id');
        $query->condition('pre.id', $preference_id);
        $result = $query->execute();
        $row = $result->fetchObject();
        $page_content .= "Are you sure you want to notify?<br><br>";
        $page_content .= "Book: <b>{$row->book}</b><br>";
        $page_content .= "Author: <b>{$row->author}</b><br>";
        $page_content .= "Contributor: <b>{$row->full_name}</b><br>";
        $page_content .= "Expected Completion Date: <b>" . date("d-m-Y", $row->completion_date) . "</b><br><br>";
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // $page_content .= l("Yes", "textbook-companion/manage-proposal/failed/{$preference_id}/yes") . " | ";

        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // $page_content .= l("Cancel", "textbook-companion/manage-proposal/failed");

      }
      else {
        /*$query = "
        SELECT * FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pro.proposal_status = 1 AND pre.approval_status = 1 AND pro.completion_date < %d
        ORDER BY failed_reminder
        ";
        $result = db_query($query, time());*/
        $query = \Drupal::database()->select('textbook_companion_proposal', 'pro');
        $query->fields('pro');
        $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
        $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
        $query->condition('pro.proposal_status', 1);
        $query->condition('pre.approval_status', 1);
        $query->condition('pro.completion_date', '%time()', '<');
        $query->orderBy('failed_reminder', 'ASC');
        $result = $query->execute();
        $headers = [
          "Date of Submission",
          "Book",
          "Contributor Name",
          "Expected Completion Date",
          "Remainders",
          "Action",
        ];
        $rows = [];
        while ($row = $result->fetchObject()) {
          // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $item = array(
//                 date("d-m-Y", $row->creation_date),
//                 "{$row->book}<br><i>by</i> {$row->author}",
//                 $row->name,
//                 date("d-m-Y", $row->completion_date),
//                 $row->failed_reminder,
//                 l("Remind", "textbook-companion/manage-proposal/failed/{$row->id}")
//             );

          array_push($rows, $item);
        }
        // @FIXME
        // theme() has been renamed to _theme() and should NEVER be called directly.
        // Calling _theme() directly can alter the expected output and potentially
        // introduce security issues (see https://www.drupal.org/node/2195739). You
        // should use renderable arrays instead.
        // 
        // 
        // @see https://www.drupal.org/node/2195739
        // $page_content .= theme('table', array(
        //             'header' => $headers,
        //             'rows' => $rows
        //         ));

      }
    }
    return $page_content;
  }

  public function code_approval() {
    /* get a list of unapproved chapters */
    $query = \Drupal::database()->select('textbook_companion_example', 'e');
    $query->fields('c', [
      'id',
      'number',
      'name',
      'preference_id'
    ]);
    $query->addField('c', 'id', 'c_id');
    $query->addField('c', 'number', 'c_number');
    $query->addField('c', 'name', 'c_name');
    $query->addField('c', 'preference_id', 'c_preference_id');
    $query->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $query->condition('e.approval_status', 0);
    $query->orderBy('e.timestamp', 'DESC');
    $pending_chapter_q = $query->execute();
    if (!$pending_chapter_q) {
      \Drupal::messenger()->addStatus(t('There are no pending code approvals.'));
      return '';
    }
    $rows = [];
    while ($row = $pending_chapter_q->fetchObject()) {
      /* get preference data */
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('id', $row->c_preference_id);
      $result = $query->execute();
      $preference_data = $result->fetchObject();
      /* get proposal data */
      $query = \Drupal::database()->select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('id', $preference_data->proposal_id);
      $result = $query->execute();
      $proposal_data = $result->fetchObject();
      /* setting table row information */
      $edit_link = Link::fromTextAndUrl(t('Edit'),Url::fromUri('internal:/textbook-companion/code-approval/approve/' . $row->c_id))->toString();
      $rows[] = array(
                  $preference_data->book,
                  $row->c_number,
                  $row->c_name,
                  $proposal_data->full_name,
                  $edit_link
              );

    }
    /* check if there are any pending proposals */
    if (!$rows) {
      \Drupal::messenger()->addStatus(t('There are no pending proposals'));
      return '';
    }
    $header = [
      'Title of the Book',
      'Chapter Number',
      'Title of the Chapter',
      'Contributor Name',
      'Actions',
    ];
    
    $output = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No data found')
  ];

    return $output;
  }

  public function list_chapters() {
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
    if (!$proposal_data) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
$msg = drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');
$response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
      //drupal_goto('');
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $msg = \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
          $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
          break;
        case 2:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
         $msg = drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');
          $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
          break;
        case 3:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          $msg = drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
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
    if ($preference_data->submited_all_examples_code == 1) {
      $msg = \Drupal::messenger()->addStatus(t('You have submited your all codes for this book to review, hence you can not upload more code, for any query please write us.'));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
    }
    if (!$preference_data) {
      $msg = \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
    }
    /************************ end approve book details **************************/
    // $return_html = '<br />';
    // $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
    // $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
    $return_html = [
    '#markup' => '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />' .
                 '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />'
  ];
  // Link to 'Upload Solution' page.
  $upload_solution_url = Url::fromRoute('textbook_companion.upload_examples');
  $return_html['#markup'] .= Link::fromTextAndUrl('Upload Solution', $upload_solution_url)->toString() . '<br />';
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $return_html .= l('Upload Example Code', 'textbook-companion/code/upload') . '<br />';

    /* get chapter list */
    $chapter_rows = [];
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_data->id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_data->id);
    $query->orderBy('number', 'ASC');
    $chapter_q = $query->execute();
    while ($chapter_data = $chapter_q->fetchObject()) {
      /* get example list */
      /* $example_q = db_query("SELECT count(*) as example_count FROM {textbook_companion_example} WHERE chapter_id = %d", $chapter_data->id);
        $example_data = db_fetch_object($example_q);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->addExpression('count(*)', 'example_count');
      $query->condition('chapter_id', $chapter_data->id);
      $result = $query->execute();
      $example_data = $result->fetchObject();
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      $edit_link = Link::fromTextAndUrl(t('Edit'),Url::fromUri('internal:/textbook-companion/code/chapter/edit/' . $chapter_data->id))->toString();
$view_link = Link::fromTextAndUrl('View', Url::fromUri('internal:/textbook-companion/code/list-examples/' . $chapter_data->id))->toString();
// Display the chapter name with the edit link.
//$chapter_name_with_link = $chapter_data->name;
$chapter_name_with_link = t('@chapterName | @editLink', array('@chapterName' => $chapter_data->name,'@editLink' => $edit_link));
      $chapter_rows[] = array(
                  $chapter_data->number,
                  $chapter_name_with_link,
                  $example_data->example_count,
                  $view_link
              );

    }
    /* check if there are any chapters */
    // if (!$chapter_rows) {
    //   \Drupal::messenger()->addStatus(t('No uploads found.'));
    //   return $return_html;
    // }
    $chapter_header = [
      'Chapter No.',
      'Title of the Chapter',
      'Uploaded Examples',
      'Actions',
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
     $return_html[] = [
    '#theme' => 'table',
    '#header' => $chapter_header,
    '#rows' => $chapter_rows,
    '#empty' => t('No uploads found')
  ];
    // $return_html .= theme('table', array(
    //         'header' => $chapter_header,
    //         'rows' => $chapter_rows
    //     ));

    //$submited_all_example = \Drupal::formBuilder()->getForm("all_example_submitted_check_form", $preference_data->id);
    //$return_html .= \Drupal::service("renderer")->render($submited_all_example);
    return $return_html;
  }

  // public function upload_examples() {
  //   return \Drupal::formBuilder()->getForm('upload_examples_form');
  // }

  public function _upload_examples_delete() {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('textbook_companion_global');
    $root_path = $service->textbook_companion_path();
    $route_match = \Drupal::routeMatch();
    $example_id = (int) $route_match->getParameter('example_id');
    //var_dump($example_id);die;
    /* check example */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    if (!$example_data) {
     $msg = \Drupal::messenger()->addError(t("Invalid example selected."));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  return $msg;
    }
    if ($example_data->approval_status != 0) {
      $msg = \Drupal::messenger()->addError('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.');
      
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  return $msg;
    }
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $query->range(0, 1);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    //var_dump($chapter_data);die;
    if (!$chapter_data) {
      $msg = \Drupal::messenger()->addError('You do not have permission to delete this example.');
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  return $msg;
    }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $msg = \Drupal::messenger()->addError('You do not have permission to delete this example.');
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  return $msg;
    }
    //var_dump($preference_data);die;
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $query->condition('uid', $user->id());
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    //var_dump($proposal_data);die;
    if (!$proposal_data) {
      $msg = \Drupal::messenger()->addError('You do not have permission to delete this example.');
       $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  return $msg;
    }
    /* deleting example files */
    if (delete_example($example_data->id)) {
      \Drupal::messenger()->addStatus('Example deleted.');
      /* sending email */


      $email_to = $user->mail;
      $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
      $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
      $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
      $params['example_deleted_user']['book_title'] = $preference_data->book;
      $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
      $params['example_deleted_user']['example_number'] = $example_data->number;
      $params['example_deleted_user']['example_caption'] = $example_data->caption;
      $params['example_deleted_user']['user_id'] = $user->uid;
      $params['example_deleted_user']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      // if (!drupal_mail('textbook_companion', 'example_deleted_user', $email_to, language_default(), $params, $from, TRUE)) {
      //   \Drupal::messenger()->addError('Error sending email message.');
      // }
    }
    else {
      \Drupal::messenger()->addStatus('Error deleting example.');
    }
     $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
  // return $msg;
    return;
  }

  public function list_examples() {
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
    if (!$proposal_data) {
      $url = Url::fromUri('internal:/textbook-companion/proposal');
$proposal_link = Link::fromTextAndUrl('proposal', $url)->toString();
$msg = \Drupal::messenger()->addError(t('Please submit a proposal form  at @proposal_form.',['@proposal_form' => $proposal_link]));
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
  return $msg;
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $msg = \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
          //drupal_goto('textbook-companion/code');
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
          break;
        case 2:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
$proposal_link = Link::fromTextAndUrl('proposal', $url)->toString();
$msg = \Drupal::messenger()->addError(t('Your proposal has been disapproved. Please create another proposal here @proposal_form.',['@proposal_form' => $proposal_link]));
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
  return $msg;
          break;
        case 3:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
$proposal_link = Link::fromTextAndUrl('proposal', $url)->toString();
$msg = \Drupal::messenger()->addError(t('Congratulations! You have completed your last book proposal. You have to create another proposal at @proposal_form.',['@proposal_form' => $proposal_link]));
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  $response->send();
  return $msg;
          break;
        default:
          $msg = \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
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
    if (!$preference_data) {
      $msg = \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      $response->send();
      return $msg;
    }
    /************************ end approve book details **************************/
    /* get chapter details */
    $route_match = \Drupal::routeMatch();
    $chapter_id = (int) $route_match->getParameter('chapter_id');
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d LIMIT 1", $chapter_id, $preference_data->id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $query->range(0, 1);
    $chapter_q = $query->execute();
    $return_html = [];
    if ($chapter_data = $chapter_q->fetchObject()) {
      $return_html['html_output'] = [
      '#type' => 'markup',
      '#markup' => '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />
      <strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />
      <strong>Chapter Number:</strong><br />' . $chapter_data->number . '<br /><br />
      <strong>Title of the Chapter:</strong><br />' . $chapter_data->name . '<br />',
    ];
      // $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
      // $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
      // $return_html .= '<strong>Chapter Number:</strong><br />' . $chapter_data->number . '<br /><br />';
      // $return_html .= '<strong>Title of the Chapter:</strong><br />' . $chapter_data->name . '<br />';
    }
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid chapter.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
      $response->send();
      return $msg;
    }
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $return_html .= '<br />' . l('Back to Chapter List', 'textbook-companion/code');

    /* get example list */
    $example_rows = [];
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
      /* approval status */
      $approval_status = '';
      switch ($example_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Rejected';
          break;
      }
      /* example files */
      //$example_files = '';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d ORDER BY filetype", $example_data->id);*/
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_data->id);
      $query->orderBy('filetype', 'ASC');
      $example_files_q = $query->execute();
      while ($example_files_data = $example_files_q->fetchObject()) {
        $file_type = '';
        switch ($example_files_data->filetype) {
          case 'S':
            $file_type = 'Main or Source';
            break;
          case 'R':
            $file_type = 'Result';
            break;
          case 'X':
            $file_type = 'xcos';
            break;
          default:
        }
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        $download_url = Url::fromUri('internal:/textbook-companion/download/file/' . $example_files_data->id);

// Create a link for the file.
$file_link = Link::fromTextAndUrl($example_files_data->filename, $download_url);

// Render the link and append the file type and line break.
$example_files_link = $file_link->toString();
$example_files_type = $file_type;
//$example_files =  $example_files_link . $example_files_markup;
        // $example_files .= l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id) . ' (' . $file_type . ')<br />';

      }
      if ($example_data->approval_status == 0) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
$edit_url = Url::fromUri('internal:/textbook-companion/code/edit/' . $example_data->id);
$edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toString();

// Create the Delete link with a confirmation dialog.
// $delete_url = Url::fromUri('internal:/textbook-companion/code/delete/' . $example_data->id);
// $delete_link = Link::fromTextAndUrl(t('Delete'), $delete_url)
//   ->toRenderable()
//   ->setAttribute('onclick', 'return confirm("Are you sure you want to delete the example?");');

// $delete_link = \Drupal::service('renderer')->render($delete_link);

$url = Url::fromUri('internal:/textbook-companion/code/delete/' . $example_data->id);

// Add attributes to the link, including the confirmation dialog.
$link = Link::fromTextAndUrl(t('Delete'), $url);
$link = $link->toRenderable();
$link['#attributes']['class'][] = 'confirm-link';
$link['#attributes']['onclick'] = 'return confirm("Are you sure you want to proceed?");';

// Render the link.
$rendered_link = \Drupal::service('renderer')->render($link);
// Combine the links.
$mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $edit_link, '@linkReject' => $rendered_link));
//$links = $edit_link . '| ' . $rendered_link;
$example_rows[] = array(
                'data' => array(
                    $example_data->number,
                    $example_data->caption,
                    $approval_status,
                    $example_files_link,
                    $example_files_type,
                    $mainLink
                ),
                'valign' => 'top'
            );

      }
      else {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
$url = Url::fromUri('internal:/textbook-companion/download/example/' . $example_data->id);
$download_link = Link::fromTextAndUrl(t('Download'), $url)->toString();
$example_rows[] = array(
                'data' => array(
                    $example_data->number,
                    $example_data->caption,
                    $approval_status,
                    $example_files_link,
                    $example_files_type,
                    $download_link
                ),
                'valign' => 'top'
            );

      }
    }
    $example_header = [
      'Example No.',
      'Caption',
      'Status',
      'Files',
      'Type of the file',
      'Action',
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    $return_html['table_output'] = [
      '#type' => 'table',
      '#header' => $example_header,
      '#rows' => $example_rows,
      '#empty' => $this->t('No data available.'),
    ];

    

    return $return_html;
  }

  public function textbook_companion_browse_book() {
    $return_html = _browse_list('book');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the title of the book";
      return $return_html;
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE book like '%s%%' AND approval_status = 1", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('book', '' . $query_character . '%%', 'like');
    $query->condition('approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $book_rows[] = array(
//             l($book_data->book, 'textbook_run/' . $book_data->id),
//             $book_data->author
//         );

    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that title";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Author Name',
      ];
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      // 
      // 
      // @see https://www.drupal.org/node/2195739
      // $return_html .= theme('table', array(
      //             'headers' => $book_header,
      //             'rows' => $book_rows
      //         ));

    }
    return $return_html;
  }

  public function textbook_companion_browse_author() {
    $return_html = _browse_list('author');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the author's name";
      return $return_html;
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as id FROM {textbook_companion_preference} pe RIGHT JOIN  {textbook_companion_proposal} po on pe.proposal_id=po.id  WHERE po.proposal_status=3 and pe.approval_status = 1", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference', 'pe');
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->rightJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      /* Initial's fix algorithm */
      preg_match_all("/{$query_character}[a-z]+/", $book_data->author, $matches);
      if (count($matches) > 0) {
        /* Remove the word "And"/i from the match array and make match bold */
        if (count($matches[0]) > 0) {
          foreach ($matches[0] as $key => $value) {
            if (strtolower($value) == "and") {
              unset($matches[$key]);
            }
            else {
              $matches[0][$key] = "<b>" . $value . "</b>";
              $book_data->author = str_replace($value, $matches[0][$key], $book_data->author);
            }
          }
        }
        /* Check count of matches after removing And */
        if (count($matches[0]) > 0) {
          // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $book_rows[] = array(
//                     l($book_data->book, 'textbook_run/' . $book_data->id),
//                     $book_data->author
//                 );

        }
      }
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that author's name";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Author Name',
      ];
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      // 
      // 
      // @see https://www.drupal.org/node/2195739
      // $return_html .= theme('table', array(
      //             'headers' => $book_header,
      //             'rows' => $book_rows
      //         ));

    }
    return $return_html;
  }

  public function textbook_companion_browse_student() {
    $return_html = _browse_list('student');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    //print $query_character;
    //die();
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the student's name";
      return $return_html;
    }
    $book_rows = [];
    /*$student_q = db_query("
    SELECT po.full_name, pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id, po.approval_date as approval_date
    FROM textbook_companion_preference pe LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id 
    WHERE po.proposal_status = 3 AND pe.approval_status = 1 AND full_name LIKE '%s%%'
    ", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference', 'pe');
    $query->fields('po', [
      'full_name',
      'approval_date',
    ]);
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->condition('full_name', '' . $query_character . '%%', 'LIKE');
    $student_q = $query->execute();
    while ($student_data = $student_q->fetchObject()) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $book_rows[] = array(
//             l($student_data->book, 'textbook_run/' . $student_data->pe_id),
//             $student_data->full_name
//         );

    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that student's name";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Student Name',
      ];
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      // 
      // 
      // @see https://www.drupal.org/node/2195739
      // $return_html .= theme('table', array(
      //             'headers' => $book_header,
      //             'rows' => $book_rows
      //         ));

    }
    return $return_html;
  }

  public function textbook_companion_download_example_file() {
    $route_match = \Drupal::routeMatch();
    $example_file_id = (int) $route_match->getParameter('example_file_id');
    //var_dump($example_file_id);die;
    $service = \Drupal::service('textbook_companion_global');
    $root_path = $service->textbook_companion_path();
    $example_files_q = \Drupal::database()->query(
      "SELECT tcef.*,tcp.* FROM {textbook_companion_example_files} tcef
       JOIN {textbook_companion_example} tce ON tcef.example_id = tce.id
       JOIN {textbook_companion_chapter} tcc ON tce.chapter_id = tcc.id
       JOIN {textbook_companion_preference} tcp ON tcc.preference_id = tcp.id
       WHERE tcef.example_id = :example_id LIMIT 1",
      [':example_id' => $example_file_id]
    );

    $example_file_data = $example_files_q->fetchObject();
//var_dump($example_file_data);die;
    // Check if the file data exists.
    if (!$example_file_data) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Construct the file path.
    $file_path = $root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath;
//var_dump($file_path);die;
    // Check if the file exists on the server.
    if (!file_exists($file_path)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Create a BinaryFileResponse to force download.
    $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    str_replace(' ', '_', $example_file_data->filename)
  );

    // Set the content type header.
   // $response->headers->set('Content-Type', $example_file_data->filemime);

    return $response;

  }

  public function textbook_companion_download_sample_code() {
    //$proposal_id = arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
$service = \Drupal::service('textbook_companion_global');
    $root_path = $service->textbook_companion_samplecode_path();
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    $samplecodename = substr($example_file_data->samplefilepath, strrpos($example_file_data->samplefilepath, '/') + 1);
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header('Content-Length: ' . filesize($root_path . $example_file_data->samplefilepath));
    ob_clean();
    readfile($root_path . $example_file_data->samplefilepath);
  }

  public function textbook_companion_download_example() {
    $route_match = \Drupal::routeMatch();
    $example_id = (int) $route_match->getParameter('example_id');
    //var_dump("hi");die;
    $service = \Drupal::service('textbook_companion_global');
    $root_path = $service->textbook_companion_path();
    $root_temp_path = $service->textbook_companion_temp_path();
    /* get example data */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    /* $query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();*/
    $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
      ':example_id' => $example_id
      ]);
    $EX_PATH = 'EX' . $example_data->number . '/';
    /* zip filename */
    if (!is_dir($root_temp_path . 'tbc_download_temp')) {
      mkdir($root_temp_path . 'tbc_download_temp');
    }
    $zip_filename = $root_temp_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

// Create a zip archive on the server
$zip = new \ZipArchive();
if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
  $msg = \Drupal::messenger()->addError(t('Failed to create zip file.'));
  return $msg;
}

// Add files to the zip
while ($example_files_row = $example_files_q->fetchObject()) {
  $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
  //var_dump($file_path);die;
  $entry_name = $EX_PATH . $example_files_row->filename;
  //var_dump($entry_name);die;
  if (file_exists($file_path)) {
    //var_dump($file_path);die;
    $zip->addFile($file_path, $entry_name);
  }
}
$zip_file_count = $zip->numFiles;
$zip->close();

//var_dump($zip_file_count);die;
// Check if files were added to the zip
if ($zip_file_count > 0 && file_exists($zip_filename)) {
  // Create a BinaryFileResponse for the download
 // var_dump("hi");die;
  $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'EX' . $example_data->number . '.zip'
  );
  $response->deleteFileAfterSend(TRUE); // Delete the file after sending it

  return $response;
} else {
  \Drupal::messenger()->addError(t('There are no files in this example to download.'));
  // Redirect back to the previous page
  return $this->redirect('<current>');
}
  }

  public function textbook_companion_download_chapter() {
    $route_match = \Drupal::routeMatch();
    $chapter_id = (int) $route_match->getParameter('chapter_id');
    $service = \Drupal::service('textbook_companion_global');
    //var_dump($chapter_id);die;
    $root_path = $service->textbook_companion_path();
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    if (!is_dir($root_path . 'tbc_download_temp')) {
      mkdir($root_path . 'tbc_download_temp');
    }
    $zip_filename = $root_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
        ':example_id' => $example_row->id
        ]);
      while ($example_files_row = $example_files_q->fetchObject()) {
        $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'CH' . $chapter_data->number . '.zip'
  );
  $response->deleteFileAfterSend(TRUE); // Delete the file after sending it

  return $response;
      /* download zip file */
      // header('Content-Type: application/zip');
      // header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
      // header('Content-Length: ' . filesize($zip_filename));
      // ob_clean();
      // readfile($zip_filename);
      // unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addError("There are no examples in this chapter to download");
      return $this->redirect('<current>');
    }
  }

  public function textbook_companion_download_book() {
    $preference_id = arg(2);
    _latex_copy_script_file();
    $full_book = arg(3);
    if ($full_book == "1") {
      _latex_generate_files($preference_id, TRUE);
    }
    else {
      _latex_generate_files($preference_id, FALSE);
    }
  }
public function textbook_companion_download_completed_book() {
$user = \Drupal::currentUser();
    
    $route_match = \Drupal::routeMatch();

$book_id = (int) $route_match->getParameter('preference_id');
$serivce = \Drupal::service("textbook_companion_global");
$root_path = $serivce->textbook_companion_path();
    $root_temp_path = $serivce->textbook_companion_temp_path();
    $database = \Drupal::database();

// Query the database
$query = $database->select('textbook_companion_preference', 'tcp');
$query->fields('tcp');
$query->condition('id', $book_id);
$result = $query->execute();
$book_data = $result->fetchObject();

// Process the data
$zipname = str_replace(' ', '_', $book_data->book);
$directory_name = $book_data->directory_name;
$BK_PATH = $zipname . '/';
$temp_dir = $root_temp_path . 'tbc_download_temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Generate a unique zip filename
$zip_filename = $temp_dir . '/zip-' . time() . '-' . rand(0, 999999) . '.zip';

// Create a new zip archive
$zip = new \ZipArchive();
if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
    throw new \RuntimeException("Cannot open zip file for writing: $zip_filename");
}

// Query chapters from the database
$database = \Drupal::database();
$query = $database->select('textbook_companion_chapter', 'tcc');
$query->fields('tcc');
$query->condition('preference_id', $book_id);
$chapter_q = $query->execute();
// Iterate through chapters
while ($chapter_row = $chapter_q->fetchObject()) {
    $CH_PATH = 'CH' . $chapter_row->number . '/';

    // Query examples for the current chapter
    $example_query = \Drupal::database()->select('textbook_companion_example', 'tce');
    $example_query->fields('tce');
    $example_query->condition('chapter_id', $chapter_row->id);
    $example_query->condition('approval_status', 1);
    $example_q = $example_query->execute();

    // Iterate through examples
    while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';

        // Query files for the current example
        $file_query = \Drupal::database()->select('textbook_companion_example_files', 'tcef');
        $file_query->fields('tcef');
        $file_query->condition('example_id', $example_row->id);
        $example_files_q = $file_query->execute();

        // Add each file to the zip
        while ($example_files_row = $example_files_q->fetchObject()) {
            $source = $root_path . $directory_name . '/' . $example_files_row->filepath;
            $destination = $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename;
            if (file_exists($source)) {
                $zip->addFile($source, $destination);
            } else {
                \Drupal::logger('tbc_download')->warning('File not found: ' . $source);
            }
        }
    }
}
$zip_file_count = $zip->numFiles;
$zip->close();

if ($zip_file_count > 0) {
    // Download zip file
    $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    $book_data->book . '.zip'
  );
  $response->deleteFileAfterSend(TRUE); // Delete the file after sending it

  return $response;
    // $response = new \Drupal\Core\File\FileResponse(
    //     $zip_filename,
    //     str_replace(' ', '_', $book_data->book) . '.zip',
    //     'application/zip'
    // );
    // $response->send();
    // // Delete the temporary zip file after download
    // if (file_exists($zip_filename)) {
    //     unlink($zip_filename);
    // }
    // exit;
} else {
    \Drupal::messenger()->addError(t("There are no examples in this book to download"));
    return new \Drupal\Core\Routing\TrustedRedirectResponse('/textbook-companion/completed-books');
}
}

  public function textbook_companion_download_full_chapter() {
    $chapter_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_row->id);
      $example_files_q = $query->execute();
      while ($example_files_row = $example_files_q->fetchObject()) {
        $zip->addFile($root_path . $example_files_row->filepath, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
    }
    /* unapproved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
        ':example_id' => $example_row->id
        ]);
      /*$query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();*/
      while ($example_files_row = $example_files_q->fetchObject()) {
        $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Pragma: no-cache');
      ob_end_flush();
      ob_clean();
      flush();
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addError("There are no examples in this chapter to download");
      drupal_goto('textbook-companion/code-approval/bulk');
    }
  }

  public function textbook_companion_download_full_book() {
    $book_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $book_q = $query->execute();
    $book_data = $book_q->fetchObject();
    //$zipname = str_replace(' ','_',($book_data->book));
    //$BK_PATH = $zipname . '/';
    $BK_PATH = $book_data->book . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 1);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
      /* unapproved examples */
      /* $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 0);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      ob_clean();
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addError("There are no examples in this book to download");
      drupal_goto('textbook-companion/code-approval/bulk');
    }
  }

  public function textbook_companion_delete_book() {
    $book_id = arg(2);
    del_book_pdf($book_id);
    \Drupal::messenger()->addStatus(t('Book schedule for regeneration.'));
    drupal_goto('code_approval/bulk');
    return;
  }

  public function textbook_companion_ajax() {
    $query_type = arg(2);
    if ($query_type == 'chapter_title') {
      $chapter_number = arg(3);
      $preference_id = arg(4);
      /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
      $query = \Drupal::database()->select('textbook_companion_chapter');
      $query->fields('textbook_companion_chapter');
      $query->condition('number', $chapter_number);
      $query->condition('preference_id', $preference_id);
      $query->range(0, 1);
      $chapter_q = $query->execute();
      if ($chapter_data = $chapter_q->fetchObject()) {
        echo $chapter_data->name;
        return;
      } //$chapter_data = $chapter_q->fetchObject()
    } //$query_type == 'chapter_title'
    else {
      if ($query_type == 'example_exists') {
        $chapter_number = arg(3);
        $preference_id = arg(4);
        $example_number = arg(5);
        $chapter_id = 0;
        /* $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
        $query = \Drupal::database()->select('textbook_companion_chapter');
        $query->fields('textbook_companion_chapter');
        $query->condition('number', $chapter_number);
        $query->condition('preference_id', $preference_id);
        $query->range(0, 1);
        $chapter_q = $query->execute();
        if (!$chapter_data = $chapter_q->fetchObject()) {
          echo '';
          return;
        } //!$chapter_data = $chapter_q->fetchObject()
        else {
          $chapter_id = $chapter_data->id;
        }
        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s' LIMIT 1", $chapter_id, $example_number);*/
        $query = \Drupal::database()->select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('chapter_id', $chapter_id);
        $query->condition('number', $example_number);
        $query->range(0, 1);
        $example_q = $query->execute();
        if ($example_data = $example_q->fetchObject()) {
          if ($example_data->approval_status == 1) {
            echo 'Warning! Example already approved. You cannot upload the same example again.';
          }
          else {
            echo 'Warning! Example already uploaded. Delete the example and reupload it.';
          }
          return;
        } //$example_data = $example_q->fetchObject()
      }
    } //$query_type == 'example_exists'
    echo '';
  }

  public function _data_entry_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 ORDER BY book ASC");*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->orderBy('book', 'ASC');
    $preference_q = $query->execute();
    $sno = 1;
    while ($preference_data = $preference_q->fetchObject()) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $proposal_rows[] = array(
//             $sno++,
//             $preference_data->book,
//             $preference_data->author,
//             $preference_data->isbn,
//             l('Edit', 'textbook-companion/dataentry-edit/' . $preference_data->id)
//         );

    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
      \Drupal::messenger()->addStatus(t('There are no proposals.'));
      return '';
    }
    $proposal_header = [
      'SNO',
      'Title of the Book',
      'Author',
      'ISBN',
      '',
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $output = theme('table', array(
    //         'headers' => $proposal_header,
    //         'rows' => $proposal_rows
    //     ));

    return $output;
  }

  public function dataentry_edit($id = NULL) {
    if ($id) {
      return \Drupal::formBuilder()->getForm('dataentry_edit_form', $id);
    }
    else {
      return 'Access denied';
    }
  }

  public function _list_all_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM textbook_companion_proposal WHERE proposal_status=3 AND uid= :uid", [
      ':uid' => $user->uid
      ]);
    $exist_id = $query_id->fetchObject();
    $exist_id_count = $query_id->rowCount();
    if ($exist_id) {
      if ($exist_id->id) {
        if ($exist_id_count < 1) {
          \Drupal::messenger()->addStatus(t('<strong>You need to propose a book <a href="http://om.fossee.in/textbook-companion/proposal">Book Proposal</a></strong> or if you have already proposed then your book is under reviewing process'));
          return '';
        } //$exist_id->id < 3
        else {
          $search_rows = [];
          global $output;
          $output = '';
          $query3 = \Drupal::database()->query("SELECT prop.id,pref.isbn,pref.book,pref.author FROM textbook_companion_proposal as prop,textbook_companion_preference as pref WHERE prop.proposal_status=3 AND pref.approval_status=1 AND pref.proposal_id=prop.id AND prop.uid= :uid", [
            ':uid' => $user->uid
            ]);
          while ($search_data3 = $query3->fetchObject()) {
            if ($search_data3->id) {
              // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $search_rows[] = array(
// 						$search_data3->isbn,
// 						$search_data3->book,
// 						$search_data3->author,
// 						l('Download Certificate', 'textbook-companion/certificate/generate-pdf/' . $search_data3->id)
// 					);

            } //$search_data3->id
          } //$search_data3 = $query3->fetchObject()
          if ($search_rows) {
            $search_header = [
              'ISBN',
              'Book Name',
              'Author',
              'Download Certificates',
            ];
            // @FIXME
            // theme() has been renamed to _theme() and should NEVER be called directly.
            // Calling _theme() directly can alter the expected output and potentially
            // introduce security issues (see https://www.drupal.org/node/2195739). You
            // should use renderable arrays instead.
            // 
            // 
            // @see https://www.drupal.org/node/2195739
            // $output        = theme('table', array(
            // 					'header' => $search_header,
            // 					'rows' => $search_rows
            // 				));

            return $output;
          } //$search_rows
          else {
            echo ("Error");
            return '';
          }
        }
      }
    } //$exist_id->id
    else {
      \Drupal::messenger()->addStatus(t('<strong>You need to propose a book <a href="http://om.fossee.in/textbook-companion/proposal">Book Proposal</a></strong> or if you have already proposed then your book is under reviewing process'));
      $page_content = "<span style='color:red;'> No certificate available </span>";
      return $page_content;
    }
  }

  public function verify_certificates($qr_code = 0) {
    $qr_code = arg(3);
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = \Drupal::formBuilder()->getForm("verify_certificates_form");
      $page_content = \Drupal::service("renderer")->render($verify_certificates_form);
    }
    return $page_content;
  }

  public function textbook_companion_nonaicte_proposal_all() {
    $user = \Drupal::currentUser();
    $page_content = "";
    if (!$user->uid) {
      $page_content .= "<ul>";
      $page_content .= "<li>Please <a href='/user'><b><u>Login</u></b></a> to create a proposal.</li>";
      $page_content .= "</ul>";
      return $page_content;
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
            drupal_goto('');
            return;
            break;
          case 1:
            // @FIXME
            // l() expects a Url object, created from a route name or external URI.
            // drupal_set_message(t('Your proposal has been approved. Please go to ' . l('Code Submission', 'textbook-companion/code') . ' to upload your code'), 'status');

            drupal_goto('');
            return;
            break;
          case 2:
            \Drupal::messenger()->addError(t('Your proposal has been dis-approved. Please create another proposal below.'));
            break;
          case 3:
            \Drupal::messenger()->addStatus(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
            break;
          case 5:
            \Drupal::messenger()->addStatus(t('You have submitted your all codes.'));
            drupal_goto('');
            return;
            break;
          default:
            \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
            drupal_goto('');
            return;
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    //variable_del("aicte_".$user->uid);
    $book_proposal_nonaicte_form = \Drupal::formBuilder()->getForm("book_proposal_nonaicte_form");
    $page_content .= \Drupal::service("renderer")->render($book_proposal_nonaicte_form);
    return $page_content;
  }

}
