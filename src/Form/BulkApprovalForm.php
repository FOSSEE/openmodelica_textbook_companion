<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BulkApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Url;

class BulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_approval_form';
  }

public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_books();
    $options_two = $this->_ajax_bulk_get_chapter_list();
    $selected = !$form_state->getValue(['book']) ? $form_state->getValue([
      'book'
      ]) : key($options_first);
    $select_two = !$form_state->getValue(['chapter']) ? $form_state->getValue([
      'chapter'
      ]) : key($options_two);
    $form['book'] = [
      '#type' => 'select',
      '#title' => t('Title of the Book'),
      '#options' => $this->_bulk_list_of_books(),
      '#default_value' => $selected,
      // '#tree' => TRUE,
     // $form['download_book']['chapter'],
      '#ajax' => [
        'callback' => '::ajax_bulk_chapter_list_callback',
        'wrapper' => 'ajax_selected_book'
        ],
      '#validated' => TRUE,
    ];
    $form['download_book'] = [
     // '#type' => 'item',
      //'#markup' => '<div id="ajax_selected_book"></div>',
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_book'],
    ];
    $book_default_value = $form_state->getValue('book');
    $form['download_book']['selected_book'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Book'),
        Url::fromUri('internal:/textbook-companion/full-download/book/' . $book_default_value)
      )->toString() . ' ' . $this->t('(Download all the approved and unapproved solutions of the entire book)'),
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],

      ];
    $form['download_book']['book_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected book'),
      '#options' => $this->_bulk_list_book_actions(),
      //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
      //   '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
      // '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
      '#validated' => TRUE,
    ];
    $form['download_book']['chapter'] = [
      '#type' => 'select',
      '#title' => t('Title of the Chapter'),
      '#options' => $this->_ajax_bulk_get_chapter_list($book_default_value),
      //'#default_value' => $chapter_default_value,
        '#prefix' => '<div id="ajax_select_chapter_list">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#tree' => TRUE,
      '#ajax' => [
        'callback' => '::ajax_bulk_example_list_callback',
        'wrapper' => 'ajax_download_chapter'
        ],
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => ['value' => 0]
          ]
        ],
    ];
    $chapter_default_value = $form_state->getValue('chapter');
    $form['download_book']['download_chapter'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_chapter'],
    ];
    $form['download_book']['download_chapter']['selected_chapter_download'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Chapter'),
        Url::fromUri('internal:/textbook-companion/full-download/chapter/' . $chapter_default_value)
      )->toString() . ' ' . $this->t('(Download all the approved and unapproved solutions of the entire chapter)'),
      '#states' => [
        'invisible' => [
          ':input[name="chapter"]' => [
            'value' => 0
            ]
          ]
        ],

      ];
    $form['download_book']['download_chapter']['chapter_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected chapter'),
      '#options' => $this->_bulk_list_chapter_actions(),
      //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="chapter"]' => [
            'value' => 0
            ]
          ]
        ],
      //'#ajax' => ['callback' => 'ajax_bulk_chapter_actions_callback'],
    ];
   // var_dump(($chapter_default_value));
    $form['download_book']['download_chapter']['example'] = [
      '#type' => 'select',
      '#title' => t('Example No. (Caption)'),
      '#options' => $this->_ajax_bulk_get_examples($chapter_default_value),
      // '#default_value' => $example_default_value,       
        '#validated' => TRUE,
      // '#prefix' => '<div id="ajax_selected_example">',
      // '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="chapter"]' => [
            'value' => 0
            ]
          ]
        ],
      '#ajax' => [
        'callback' => '::ajax_bulk_example_files_callback',
        'wrapper' => 'ajax_download_selected_example'
        ],
    ];
    // $example_default_value = $form_state->getValue('example');
    $example_default_value = $form_state->getValue('example') ?? $form_state->getTriggeringElement()['#value'];
    $form['download_book']['download_chapter']['download_example'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_selected_example'],
    ];
  $form['download_book']['download_chapter']['download_example']['selected_example_download'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Example'),
        Url::fromUri('internal:/textbook-companion/download/example/' . $example_default_value)
      )->toString() . '<br>',
      '#states' => [
        'invisible' => [
          ':input[name="example"]' => [
            'value' => 0
            ]
          ]
        ],

      ];
    $form['download_book']['download_chapter']['download_example']['edit_example'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Edit Example'),
        Url::fromUri('internal:/textbook-companion/code-approval/editcode/' . $example_default_value)
      )->toString(),
      '#states' => [
        'invisible' => [
          ':input[name="example"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['download_book']['download_chapter']['download_example']['example_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected example'),
      '#options' => $this->_bulk_list_example_actions(),
      //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['download_book']['download_chapter']['download_example']['message'] = [
  '#type' => 'textarea',
  '#title' => $this->t('If Dis-Approved, please specify reason for Dis-Approval'),
  '#states' => [
    'visible' => [
      // Show if book_actions is 3 or 4
      [':input[name="book_actions"]' => ['value' => 3]],
      [':input[name="book_actions"]' => ['value' => 4]],
      // OR if chapter_actions is 3
      [':input[name="chapter_actions"]' => ['value' => 3]],
      // OR if example_actions is 3
      [':input[name="example_actions"]' => ['value' => 3]],
    ],
    'required' => [
      // Required if book_actions is 3 or 4
      [':input[name="book_actions"]' => ['value' => 3]],
      [':input[name="book_actions"]' => ['value' => 4]],
      // OR if chapter_actions is 3
      [':input[name="chapter_actions"]' => ['value' => 3]],
      // OR if example_actions is 3
      [':input[name="example_actions"]' => ['value' => 3]],
    ],
  ],
];
//         $query = \Drupal::database()->select('textbook_companion_example_files');
//         $query->fields('textbook_companion_example_files');
//         $query->condition('example_id', $example_default_value);
//         $example_list_q = $query->execute();
//         if ($example_list_q) {
// $example_files_rows = [];

// while ($example_list_data = $example_list_q->fetchObject()) {

//   switch ($example_list_data->filetype) {
//     case 'S': $type = 'Source or Main file'; break;
//     case 'R': $type = 'Result file'; break;
//     case 'X': $type = 'xcos file'; break;
//     default: $type = 'Unknown';
//   }

//   $example_files_rows[] = [
//     Link::fromTextAndUrl(
//       $example_list_data->filename,
//       Url::fromUri('internal:/textbook-companion/download/file/' . $example_list_data->id)
//     )->toString(),
//     $type
//   ];
// }
//                 $items[] = [
//                   Link::fromTextAndUrl($example_list_data->filename, Url::fromUri('internal:/textbook-companion/download/file/' . $example_list_data->id))->toString(),
//                     "{$example_file_type}"
//                 ];
//             }
//             array_push($example_files_rows, $items);
//             //var_dump($example_files_rows);
//             /* creating list of files table */
//            $form['download_example']['example_files'] = [
//         '#type' => 'fieldset',
//         '#title' => t('List of example files'),
//       ];
//       $example_files_header = ['Filename', 'Type']; // Table headers

//       $table = [
//         '#type' => 'table',
//         '#header' => $example_files_header,
//         '#rows' => $example_files_rows,
      
//       '#attributes' => [
//         'style' => 'width: 100%;',
        
//       ],
//     ];
//           // Add the table to the fieldset
// $form['download_example']['example_files']['table'] = $table;
//     $form['download_example']['example_files'] = [
//       '#type' => 'item',
//       '#markup' => '',
//       '#prefix' => '<div id="ajax_example_files_list">',
//       '#suffix' => '</div>',
//     ];
    
   
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    return $form;
  }


  
  function ajax_bulk_chapter_list_callback(array &$form, FormStateInterface $form_state){
    return $form['download_book'];
  }
  function ajax_bulk_example_list_callback(array &$form, FormStateInterface $form_state){
    return $form['download_book']['download_chapter'];
  }
  // function ajax_bulk_example_files_callback(array &$form, FormStateInterface $form_state){
  //   // $example_default_value = $form_state->getValue('example');
  //   // var_dump($example_default_value);
  //   return $form['download_example'];
  // }
  function ajax_bulk_example_files_callback(array &$form, FormStateInterface $form_state){
  $form_state->setRebuild(TRUE);   // 🔥 REQUIRED
  return $form['download_book']['download_chapter']['download_example'];
}

function _bulk_list_of_books() {
  $book_titles = ['0' => t('Please select...')];
  // Create a database connection
  $database = Database::getConnection();

  // Build the query
  $query = $database->select('textbook_companion_preference', 'pp');
  $query->join('textbook_companion_proposal', 'p', 'pp.proposal_id = p.id');
  $query->join('users_field_data', 'u', 'p.uid = u.uid');

  // Select fields
  $query->addField('u', 'name');
  $query->addField('pp', 'id');
  $query->addField('pp', 'book');
  $query->addField('pp', 'author');

  // Add conditions
  $or_condition = $query->orConditionGroup()
    ->condition('pp.approval_status', 1)
    ->condition('pp.approval_status', 3);
  $query->condition($or_condition);

  // Order by book title
  $query->orderBy('pp.book', 'ASC');

  // Execute the query
  $book_titles_q = $query->execute();

  // Populate the book titles array
  foreach ($book_titles_q as $book_titles_data) {
    $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')' . ' (Proposed by ' . $book_titles_data->name . ')';
  }

  return $book_titles;
}
function _ajax_bulk_get_chapter_list($preference_id = 0) {
  $book_chapters = ['0' => t('Please select...')];
  // Create a database connection
  $database = Database::getConnection();

  // Build the query
  $query = $database->select('textbook_companion_chapter', 'tc');
  $query->fields('tc');
  $query->condition('preference_id', $preference_id);
  $query->orderBy('number', 'ASC');

  // Execute the query
  $book_chapters_q = $query->execute();

  // Populate the book chapters array
  foreach ($book_chapters_q as $book_chapters_data) {
    $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
  }

  return $book_chapters;
}
function _ajax_bulk_get_examples($chapter_id) {
  //var_dump($chapter_id);
  $book_examples = ['0' => t('Please select...')];
//$book_examples = [];
  try {
    $query = Database::getConnection()->select('textbook_companion_example', 'tce');
    $query->fields('tce');
    $query->condition('chapter_id', $chapter_id);
    // $query->condition('approval_status', 0);
    $book_examples_q = $query->execute();
    
    foreach ($book_examples_q as $book_examples_data) {
      $book_examples[$book_examples_data->id] = $book_examples_data->number . '. ' . $book_examples_data->caption;
    }
  } catch (\Exception $e) {
    \Drupal::logger('textbook_companion')->error('Error fetching examples: @error', ['@error' => $e->getMessage()]);
  }

  return $book_examples;
}

function _bulk_list_book_actions() {
  return [
    '0' => t('Please select...'),
    '1' => t('Approve Entire Book'),
    '2' => t('Pending Review Entire Book'),
    '3' => t('Dis-Approve Entire Book (This will delete all the examples in the book)'),
    '4' => t('Delete Entire Book Including Proposal'),
  ];
}

function _bulk_list_chapter_actions() {
  return [
    '0' => t('Please select...'),
    '1' => t('Approve Entire Chapter'),
    '2' => t('Pending Review Entire Chapter'),
    '3' => t('Dis-Approve Entire Chapter (This will delete all the examples in the chapter)'),
  ];
}
function _bulk_list_example_actions() {
  return [
    '0' => t('Please select...'),
    '1' => t('Approve Example'),
    '2' => t('Pending Review Example'),
    '3' => t('Dis-approve Example (This will delete the example)'),
  ];
}

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    // var_dump($root_path);die;
    //if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['book'])) {
        del_book_pdf($form_state->getValue(['book']));
      }
      if (\Drupal::currentUser()->hasPermission('bulk manage code')) {
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $form_state->getValue(['book']));
        $result = $query->execute();
        $pref_data = $result->fetchObject();
        $prop_id = $pref_data->proposal_id;
        // var_dump($prop_id);die;
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $prop_id);
        $user_query = $query->execute();
        $user_info = $user_query->fetchObject();
        // var_dump($user_info);die;
        $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
        if (($form_state->getValue(['book_actions']) == 1) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          /* approving entire book */
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = \Drupal::database()->update('textbook_companion_example');
            $query->fields([
              'approval_status' => 1,
              'approver_uid' => $user->id(),
            ]);
            $query->condition('chapter_id', $chapter_data->id);
            $query->condition('approval_status', 0);
            $num_updated = $query->execute();
          }
          $query = \Drupal::database()->update('textbook_companion_preference');
          $query->fields(['submited_all_examples_code' => 2]);
          $query->condition('id', $form_state->getValue(['book']));
          $num_updated = $query->execute();
          \Drupal::messenger()->addStatus(t('Approved Entire Book.'));
          /* email  body for bulk approval textbook*/
          // @FIXME
			$site_name = \Drupal::config('system.site')->get('name');

      $email_subject = $this->t('[@site][Textbook Companion] Your uploaded Textbook Companion examples have been approved', [
        '@site' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @full_name,\n\nYour all the uploaded examples for the book have been approved.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nBest Wishes,\n\n@site Team,\nFOSSEE,IIT Bombay", [
          '@site' => $site_name,
          '@Full Name'=> $full_name,
          '@book' => $preference_data->book ?? '',
          '@author' => $preference_data->author ?? '',
          '@isbn' => $preference_data->isbn ?? '',
          '@publisher' => $preference_data->publisher ?? '',
          '@edition' => $preference_data->edition ?? '',
          '@year' => $preference_data->year ?? '',
        ]),
      ];
    
        }
        elseif (($form_state->getValue(['book_actions']) == 2) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          /* pending entire book */
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = \Drupal::database()->update('textbook_companion_example');
            $query->fields(['approval_status' => 0]);
            $query->condition('chapter_id', $chapter_data->id);
            $num_updated = $query->execute();
          }
          \Drupal::messenger()->addStatus(t('Pending Review Entire Book.'));
          // /* email body for bulk-pending 
          			$site_name = \Drupal::config('system.site')->get('name');

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // to ensure that it uses the correct configuration object.
$email_subject = $this->t(
  '[@site] Your uploaded Textbook Companion examples have been marked as pending',
  ['@site' => $site_name]
);
          // 
          $email_body = $this->t(
  "Dear @user_name,

Your uploaded examples for the book have been marked as pending for review.
You will be able to see the examples after they are approved by one of our reviewers.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Best Wishes,

@site Team,
FOSSEE, IIT Bombay",
  [
    '@user_name' => $user_data->getDisplayName(),
    '@book' => $preference_data->book,
    '@author' => $preference_data->author,
    '@isbn' => $preference_data->isbn,
    '@publisher' => $preference_data->publisher,
    '@edition' => $preference_data->edition,
    '@year' => $preference_data->year,
    '@site' => $site_name,
  ]
);
        }
        elseif (($form_state->getValue(['book_actions']) == 3) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            //$form_state->setErrorByName('message', t(''));
            \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
            return;
          }
          $service = \Drupal::service('textbook_companion_global');
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
            \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'));
            return;
          }
          if ($service->delete_book($form_state->getValue(['book']))) {
            \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Book.'));
          }
          else {
            \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Book.'));
          }
          /* email body for bulk disapproved textbook entire book */

      $site_name = \Drupal::config('system.site')->get('name');

      $email_subject = $this->t(
          '[@site] Your uploaded Textbook Companion examples have been marked as dis-approved',
              ['@site' => $site_name]
                  );

          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
$email_body = $this->t(
  "Dear @user_name,

Your uploaded examples for the whole book have been marked as dis-approved.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Reason for dis-approval:
@reason

Best Wishes,

@site Team,
FOSSEE, IIT Bombay",
  [
    '@user_name' => $user_data->getDisplayName(), // ✅ correct for Drupal 10
    '@book' => $preference_data->book,
    '@author' => $preference_data->author,
    '@isbn' => $preference_data->isbn,
    '@publisher' => $preference_data->publisher,
    '@edition' => $preference_data->edition,
    '@year' => $preference_data->year,
    '@reason' => $form_state->getValue('message'),
    '@site' => $site_name,
  ]
);
        }
        elseif (($form_state->getValue(['book_actions']) == 4) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            //$form_state->setErrorByName('message', t(''));
            \Drupal::messenger()->addError("Please mention the reason for disapproval/deletion. Minimum 30 character required");
            return;
          }
          $service = \Drupal::service('textbook_companion_global');
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
            \Drupal::messenger()->addError(t('You do not have permission to Bulk Delete Entire Book Including Proposal.'));
            return;
          }
          if ($service->delete_book($form_state->getValue(['book']))) {
            \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Book examples.'));
            $dir_path = $root_path . $result->directory_name;
            if (is_dir($dir_path)) {
              $res = rmdir($dir_path);
              if (!$res) {
                \Drupal::messenger()->addError(t("Cannot delete Book directory : " . $dir_path . ". Please contact administrator."));
                return;
              }
            }
            else {
              \Drupal::messenger()->addStatus(t("Book directory not present : " . $dir_path . ". Skipping deleting book directory."));
            }
            /* deleting preference and proposal */
            $query = \Drupal::database()->select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('id', $form_state->getValue(['book']));
            $result = $query->execute();
            $preference_data = $result->fetchObject();
            $proposal_id = $preference_data->proposal_id;
            $query = \Drupal::database()->delete('textbook_companion_preference');
            $query->condition('proposal_id', $proposal_id);
            $num_deleted = $query->execute();
            $query = \Drupal::database()->delete('textbook_companion_proposal');
            $query->condition('id', $proposal_id);
            $num_deleted = $query->execute();
            \Drupal::messenger()->addStatus(t('Deleted Book Proposal.'));
            /* email */
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('@site_name Your uploaded Textbook Companion examples including the book proposal have been deleted', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Reason for deletion: @reason

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
    '@site_name' => $site_name,
    '@user_name' => $user_data->name,
    '@book' => $pref_data->book,
    '@author' => $pref_data->author,
    '@isbn' => $pref_data->isbn,
    '@publisher' => $pref_data->publisher,
    '@edition' => $pref_data->edition,
    '@year' => $pref_data->year,
    '@reason' => $form_state->getValue('message'),
  ])
];
          }
          else {
            \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Book.'));
          }
        }
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 1) && ($form_state->getValue(['example_actions']) == 0)) {
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = \Drupal::database()->update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->id(),
          ]);
          $query->condition('chapter_id', $form_state->getValue(['chapter']));
          $query->condition('approval_status', 0);
          $num_updated = $query->execute();
          \Drupal::messenger()->addStatus(t('Approved Entire Chapter.'));
          /* email */
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('[@site_name] Your uploaded Textbook Companion examples have been approved', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

Your all the uploaded examples for the chapter have been approved.

Title of the book : @book
Title of the chapter : @chapter

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
    '@site_name' => $site_name,
    '@user_name' => $user_data->name,
    '@book' => $pref_data->book,
    '@chapter' => $chap_data->name,
  ])
];
        }
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 2) && ($form_state->getValue(['example_actions']) == 0)) {
          /*db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $form_state['values']['chapter']);*/
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = \Drupal::database()->update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('chapter_id', $form_state->getValue(['chapter']));
          $num_updated = $query->execute();
          \Drupal::messenger()->addStatus(t('Entire Chapter marked as Pending Review.'));
          /* email */
          // @FIXME
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('[@site_name] Your uploaded Textbook Companion examples have been marked as pending', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

Your all the uploaded examples for the chapter have been marked as pending to be reviewed.

Title of the book : @book
Title of the chapter : @chapter

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
    '@site_name' => $site_name,
    '@user_name' => $user_data->name,
    '@book' => $pref_data->book,
    '@chapter' => $chap_data->name,
  ])
];     
   }
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 3) && ($form_state->getValue(['example_actions']) == 0)) {
        $service = \Drupal::service('textbook_companion_global');  
        $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            //$form_state->setErrorByName('message', t(''));
            \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
            return;
          }
          if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
            \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'));
            return;
          }
          if ($service->delete_chapter($form_state->getValue(['chapter']))) {
            \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Chapter.'));
          }
          else {
            \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Chapter.'));
          }
          /* email */
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('[@site_name] Your uploaded Textbook Companion example has been marked as dis-approved', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

Your uploaded example for the entire chapter has been marked as dis-approved.

Title of the book : @book
Title of the chapter : @chapter

Reason for dis-approval: @reason

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay', 
[
    '@site_name' => $site_name,
    '@user_name' => $user_data->getDisplayName(),
    '@book' => isset($pref_data->book) ? (string) $pref_data->book : '',
    '@chapter' => isset($chap_data->name) ? (string) $chap_data->name : '',
    '@reason' => (string) $form_state->getValue('message'),
  ])
];    
    }
    
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 1)) {
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          $query = \Drupal::database()->update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->id(),
          ]);
          $query->condition('id', $form_state->getValue(['example']));
          $num_updated = $query->execute();
          \Drupal::messenger()->addStatus(t('Example approved.'));
          /* email */
          // @FIXME
         $site_name = \Drupal::config('system.site')->get('name');

$email_subject = $this->t(
  '[@site] Your uploaded Textbook Companion example has been approved',
  ['@site' => $site_name]
);

          $email_body = $this->t(
  "Dear @user_name,

Your example for OpenModelica Textbook Companion with the following details is approved.

Title of the book : @book
Title of the chapter : @chapter
Example number : @example_number
Caption : @caption

Best Wishes,

@site Team,
FOSSEE, IIT Bombay",
  [
    '@user_name' => $user_data->name,
    '@book' => $pref_data->book,
    '@chapter' => $chap_data->name,
    '@example_number' => $examp_data->number,
    '@caption' => $examp_data->caption,
    '@site' => $site_name,
  ]
);

        }
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 2)) {
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          $query = \Drupal::database()->update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('id', $form_state->getValue(['example']));
          $num_updated = $query->execute();
          \Drupal::messenger()->addStatus(t('Example marked as Pending Review.'));
          /* email */
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('[@site_name] Your uploaded Textbook Companion example has been marked as pending', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

Your uploaded example for OpenModelica Textbook Companion with the following details has been marked as pending to be reviewed.

Title of the book : @book
Title of the chapter : @chapter
Example number : @example_number
Caption : @caption

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
    '@site_name' => $site_name,
    '@user_name' => $user_data->name,
    '@book' => $pref_data->book,
    '@chapter' => $chap_data->name,
    '@example_number' => $examp_data->number,
    '@caption' => $examp_data->caption,
  ])
];
        }
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 3)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            //$form_state->setErrorByName('message', t(''));
            \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
            return;
          }
          $service = \Drupal::service('textbook_companion_global');
          $query = \Drupal::database()->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = \Drupal::database()->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          if ($service->delete_example($form_state->getValue(['example']))) {
            \Drupal::messenger()->addStatus(t('Example Dis-Approved and Deleted.'));
          }
          else {
            \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Example.'));
          }
          /* email */
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Email subject
$email_subject = t('[@site_name] Your uploaded Textbook Companion example has been marked as dis-approved', [
  '@site_name' => $site_name,
]);

// Email body
$email_body = [
  t('
Dear @user_name,

Your example for OpenModelica Textbook Companion has been marked as dis-approved and deleted.

Title of the book : @book
Title of the chapter : @chapter
Example number : @example_number
Caption : @caption

Reason for dis-approval: @reason

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
    '@site_name' => $site_name,
    // '@user_name' => $user_data->name,
    // '@book' => $pref_data->book,
    // '@chapter' => $chap_data->name,
    // '@example_number' => $examp_data->number,
    // '@caption' => $examp_data->caption,
    // '@reason' => $form_state->getValue('message'),
    '@user_name' => $user_data->getDisplayName(),

'@book' => is_object($pref_data->book) ? $pref_data->book->value : $pref_data->book,

'@chapter' => is_object($chap_data->name) ? $chap_data->name->value : $chap_data->name,

'@example_number' => is_object($examp_data->number) ? $examp_data->number->value : $examp_data->number,

'@caption' => is_object($examp_data->caption) ? $examp_data->caption->value : $examp_data->caption,

'@reason' => (string) $form_state->getValue('message'),
  ])
];    
    }
        else {
          \Drupal::messenger()->addError(t('Please select only one action at a time'));
          return;
        }

        /****** sending email when everything done ******/
       if ($email_subject) {

  $mailManager = \Drupal::service('plugin.manager.mail');
  $current_user = \Drupal::currentUser();

  // $email_to = $user_data->mail;
  $email_to = $user_data->getEmail();

  $config = \Drupal::config('textbook_companion.settings');
  $from = $config->get('textbook_companion_from_email');
  $bcc = $config->get('textbook_companion_emails');
  $cc = $config->get('textbook_companion_cc_emails');

  $params = [];
  $params['subject'] = $email_subject;
  $params['body'] = $email_body;
  $params['headers'] = [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal',
    'Cc' => $cc,
    'Bcc' => $bcc,
  ];

  $langcode = $current_user->getPreferredLangcode();

  $result = $mailManager->mail(
    'textbook_companion',   // module name
    'standard',             // mail key
    $email_to,
    $langcode,
    $params,
    $from,
    TRUE
  );

  if (!$result['result']) {
    \Drupal::messenger()->addError($this->t('Error sending email message.'));
  }
}
else {
  \Drupal::messenger()->addError($this->t('You do not have permission to bulk manage code.'));
}

    }

  }
}

?>
