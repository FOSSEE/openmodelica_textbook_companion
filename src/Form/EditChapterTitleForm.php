<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditChapterTitleForm.
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

class EditChapterTitleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_chapter_title_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
    $route_match = \Drupal::routeMatch();
    $chapter_id = (int) $route_match->getParameter('chapter_id');
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      $msg = \Drupal::messenger()->addError(t('Invalid chapter.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
      $response->send();
      return $msg;
    }
    $form['#redirect'] = 'textbook-companion/code';
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
    $form['chapter_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Chapter'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $chapter_data->name,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    //         '#type' => 'markup',
    //         '#value' => l(t('Cancel'), 'textbook_companion/code')
    //     );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $service = \Drupal::service('textbook_companion_global');
    if (!$service->check_name($form_state->getValue(['chapter_title']))) {
      $form_state->setErrorByName('chapter_title', t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
    $route_match = \Drupal::routeMatch();
    $chapter_id = (int) $route_match->getParameter('chapter_id');
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      $msg = \Drupal::messenger()->addError(t('Invalid chapter.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
      return $msg;
    }
    /*db_query("UPDATE {textbook_companion_chapter} SET name = '%s' WHERE id = %d", $form_state['values']['chapter_title'], $chapter_id);*/
    $query = \Drupal::database()->update('textbook_companion_chapter');
    $query->fields(['name' => $form_state->getValue(['chapter_title'])]);
    $query->condition('id', $chapter_id);
    $num_updated = $query->execute();
    $msg = \Drupal::messenger()->addStatus(t('Title of the Chapter updated.'));
    $response = new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString());
  $response->send();
    return $msg;
  }

}
?>
