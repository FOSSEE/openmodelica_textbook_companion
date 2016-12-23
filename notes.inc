<?php
// $Id$
/******************************************************************************/
/***************************** BOOK NOTES *************************************/
/******************************************************************************/
function book_notes_form($form_state)
  {
    global $user;
    /* get current proposal */
    $preference_id = arg(2);
    $preference_id = (int) $preference_id;
    /*$result = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $result = $query->execute();
    if ($result)
      {
        if ($row = $result->fetchObject())
          {
            /* everything ok */
          }
        else
          {
            drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
            drupal_goto('code_approval/bulk');
            return;
          }
      }
    else
      {
        drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
        drupal_goto('code_approval/bulk');
        return;
      }
    /* get current notes */
    $notes = '';
    /*$notes_q = db_query("SELECT * FROM {textbook_companion_notes} WHERE preference_id = %d LIMIT 1", $preference_id);*/
    $query = db_select('textbook_companion_notes');
    $query->fields('textbook_companion_notes');
    $query->condition('preference_id', $preference_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    if ($notes_q)
      {
        $notes_data = $notes_q->fetchObject();
        $notes = $notes_data->notes;
      }
    $book_details = _book_information($preference_id);
    $form['book_details'] = array(
        '#type' => 'item',
        '#markup' => '<span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span><br />' . '<strong>Author:</strong> ' . $book_details->author . '<br />' . '<strong>Title of the Book:</strong> ' . $book_details->book . '<br />' . '<strong>Publisher:</strong> ' . $book_details->publisher . '<br />' . '<strong>Year:</strong> ' . $book_details->year . '<br />' . '<strong>Edition:</strong> ' . $book_details->edition . '<br /><br />' . '<span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span><br />' . '<strong>Contributor Name:</strong> ' . $book_details->full_name . ', ' . $book_details->course . ', ' . $book_details->branch . ', ' . $book_details->university . '<br />'
    );
    $form['notes'] = array(
        '#type' => 'textarea',
        '#rows' => 20,
        '#title' => t('Notes for Reviewers'),
        '#default_value' => $notes
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'markup',
        '#value' => l(t('Back'), 'code_approval/bulk')
    );
    return $form;
  }
function book_notes_form_submit($form, &$form_state)
  {
    global $user;
    /* get current proposal */
    $preference_id = arg(2);
    $preference_id = (int) $preference_id;
    /*$result = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $result = $query->execute();
    if ($result)
      {
        if ($row = $result->fetchObject())
          {
            /* everything ok */
          }
        else
          {
            drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
            drupal_goto('code_approval/bulk');
            return;
          }
      }
    else
      {
        drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
        drupal_goto('code_approval/bulk');
        return;
      }
    /* find existing notes */
    /*$notes_q = db_query("SELECT * FROM {textbook_companion_notes} WHERE preference_id = %d LIMIT 1", $preference_id);
    $notes_data = db_fetch_object($notes_q);*/
    $query = db_select('textbook_companion_notes');
    $query->fields('textbook_companion_notes');
    $query->condition('preference_id', $preference_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    $notes_data = $notes_q->fetchObject();
    /* add or update notes in database */
    if ($notes_data)
      {
        /*db_query("UPDATE {textbook_companion_notes} SET notes = '%s' WHERE id = %d", $form_state['values']['notes'], $notes_data->id);*/
        $query = db_update('textbook_companion_notes');
        $query->fields(array(
            'notes' => $form_state['values']['notes']
        ));
        $query->condition('id', $notes_data->id);
        $num_updated = $query->execute();
        drupal_set_message('Notes updated successfully.', 'status');
      }
    else
      {
        /*db_query("INSERT INTO {textbook_companion_notes} (preference_id, notes) VALUES (%d, '%s')", $preference_id, $form_state['values']['notes']);*/
        $query = "INSERT INTO {textbook_companion_notes} (preference_id, notes) VALUES	
			(:preference_id, :notes)";
        $args = array(
            ":preference_id" => $preference_id,
            ":notes" => $form_state['values']['notes']
        );
        $result = db_query($query, $args, array(
            'return' => Database::RETURN_INSERT_ID
        ));
        drupal_set_message('Notes added successfully.', 'status');
      }
  }
/* return proposal and author information */
function _book_information($preference_id)
  {
    /*$book_data = db_fetch_object(db_query("SELECT
    preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
    proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
    FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id = %d", $preference_id));*/
    $query = db_select('textbook_companion_proposal', 'proposal');
    $query->fields('preference', array(
        'book',
        'author',
        'isbn',
        'publisher',
        'edition',
        'year'
    ));
    $query->fields('proposal', array(
        'full_name',
        'faculty',
        'reviewer',
        'course',
        'branch',
        'university'
    ));
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id=preference.proposal_id');
    $query->condition('preference.id', $preference_id);
    $result = $query->execute();
    $book_data = $result->fetchObject();
    return $book_data;
  }
