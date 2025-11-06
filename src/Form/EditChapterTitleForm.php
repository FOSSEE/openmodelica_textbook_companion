<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditChapterTitleForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

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
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');

      drupal_goto('textbook-companion/code');
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
          drupal_goto('textbook-companion/code');
          return;
          break;
        case 2:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');

          drupal_goto('textbook-companion/code');
          return;
          break;
        case 3:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');

          drupal_goto('textbook-companion/code');
          return;
          break;
        default:
          \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
          drupal_goto('textbook-companion/code');
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
    if (!$preference_data) {
      \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
      drupal_goto('textbook-companion/code');
      return;
    }
    /************************ end approve book details **************************/
    $chapter_id = arg(4);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      \Drupal::messenger()->addError(t('Invalid chapter.'));
      drupal_goto('textbook-companion/code');
      return;
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
    if (!check_name($form_state->getValue(['chapter_title']))) {
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
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');

      drupal_goto('textbook-companion/code');
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          \Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
          drupal_goto('textbook-companion/code');
          return;
          break;
        case 2:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');

          drupal_goto('textbook-companion/code');
          return;
          break;
        case 3:
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');

          drupal_goto('textbook-companion/code');
          return;
          break;
        default:
          \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
          drupal_goto('textbook-companion/code');
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
    if (!$preference_data) {
      \Drupal::messenger()->addError(t('Invalid Book Preference status. Please contact site administrator for further information.'));
      drupal_goto('textbook-companion/code');
      return;
    }
    /************************ end approve book details **************************/
    $chapter_id = arg(4);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      \Drupal::messenger()->addError(t('Invalid chapter.'));
      drupal_goto('textbookcompanion/code');
      return;
    }
    /*db_query("UPDATE {textbook_companion_chapter} SET name = '%s' WHERE id = %d", $form_state['values']['chapter_title'], $chapter_id);*/
    $query = \Drupal::database()->update('textbook_companion_chapter');
    $query->fields(['name' => $form_state->getValue(['chapter_title'])]);
    $query->condition('id', $chapter_id);
    $num_updated = $query->execute();
    \Drupal::messenger()->addStatus(t('Title of the Chapter updated.'));
  }

}
?>
