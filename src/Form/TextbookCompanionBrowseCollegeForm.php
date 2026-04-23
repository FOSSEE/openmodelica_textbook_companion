<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionBrowseCollegeForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TextbookCompanionBrowseCollegeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_browse_college_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    // ahah_helper_register($form, $form_state);
    if ($form_state->getStorage()) {
      $usage_default_value = '0';
    }
    else {
      $usage_default_value = $form_state->getStorage();
    }
    $form['college_info'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="college-info-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    $form['college_info']['college'] = [
      '#type' => 'select',
      '#title' => t('College Name'),
      '#options' => $this->_list_of_colleges(),
      '#default_value' => $usage_default_value,
      '#ahah' => [
        'event' => 'change',
        // 'path' => ahah_helper_path([
        //   'college_info'
        //   ]),
        'wrapper' => 'college-info-wrapper',
      ],
    ];
    if ($usage_default_value != '0') {
      $form['college_info']['book_details'] = [
        '#type' => 'item',
        '#value' => $this->_list_books_by_college($usage_default_value),
      ];
    }
    return $form;
  }

  public function _list_of_colleges() {

  $connection = \Drupal::database();

  $college_names = [
    '0' => '--- select ---',
  ];

  $query = $connection->select('textbook_companion_proposal', 'tcp');

  // DISTINCT university
  $query->distinct();

  // Select only university field
  $query->addField('tcp', 'university');

  // Condition: proposal_status = 1 OR 3
  $or = $query->orConditionGroup()
    ->condition('tcp.proposal_status', 1)
    ->condition('tcp.proposal_status', 3);

  $query->condition($or);

  // Order by university
  $query->orderBy('tcp.university', 'ASC');

  $result = $query->execute();

  foreach ($result as $row) {
    if (!empty($row->university)) {
      $college_names[$row->university] = $row->university;
    }
  }

  return $college_names;
}

public function _list_books_by_college($college) {

  $connection = \Drupal::database();

  // ✅ Sanitize & validate input
  $college = trim((string) $college);

  if (empty($college) || $college === '0') {
    return '<p>Please select a valid college.</p>';
  }

  // ✅ Build query
  $query = $connection->select('textbook_companion_proposal', 'pro');

  // Join preference table
  $query->innerJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');

  // Fields
  $query->fields('pro', ['full_name', 'proposal_status']);
  $query->fields('pre', ['id', 'book', 'isbn']);

  // Conditions
  $query->condition('pro.university', $college);

  $or = $query->orConditionGroup()
    ->condition('pro.proposal_status', 1)
    ->condition('pro.proposal_status', 3);

  $query->condition($or);
  $query->condition('pre.approval_status', 1);

  $result = $query->execute();

  // ✅ Build output
  $output = '<table border="1" cellpadding="5" cellspacing="0">';
  $output .= '<tr>
    <th>SNO</th>
    <th>Name</th>
    <th>Book</th>
    <th>ISBN</th>
    <th>Status</th>
  </tr>';

  $sno = 1;
  $has_data = FALSE;

  foreach ($result as $row) {

    $has_data = TRUE;

    if ($row->proposal_status == 1) {

      $output .= '<tr>
        <td>' . $sno++ . '</td>
        <td>' . $row->full_name . '</td>
        <td>' . $row->book . '</td>
        <td>' . str_replace("-", "", $row->isbn) . '</td>
        <td style="color: orange;">Approved</td>
      </tr>';

    } else {

      $url = \Drupal\Core\Url::fromUserInput('/textbook_run/' . $row->id)->toString();

      $output .= '<tr>
        <td>' . $sno++ . '</td>
        <td>' . $row->full_name . '</td>
        <td><a target="_blank" href="' . $url . '">' . $row->book . '</a></td>
        <td>' . str_replace("-", "", $row->isbn) . '</td>
        <td style="color: green;">Completed</td>
      </tr>';
    }
  }

  // ✅ Handle no results
  if (!$has_data) {
    $output .= '<tr><td colspan="5">No records found.</td></tr>';
  }

  $output .= '</table>';

  return $output;
} 

public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

}
?>
