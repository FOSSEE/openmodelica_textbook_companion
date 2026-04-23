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
      // '#tree' => TRUE,
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
    $form['download_chapter'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_chapter'],
    ];
    $form['download_chapter']['selected_chapter_download'] = [
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
    $form['download_chapter']['chapter_actions'] = [
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
    $form['download_chapter']['example'] = [
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
    $example_default_value = $form_state->getValue('example');
    $form['download_example'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_selected_example'],
    ];
  $form['download_example']['selected_example_download'] = [
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
    $form['download_example']['edit_example'] = [
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
    $form['download_example']['example_actions'] = [
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
     $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
      '#states' => [
        'visible' => [
          [
            [
              ':input[name="book_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="chapter_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="example_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="book_actions"]' => ['value' => 4]],
          ]
          ],
        'required' => [
          [
            [':input[name="book_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="chapter_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="example_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="book_actions"]' => [
                'value' => 4
                ]
              ],
          ]
          ],
      ],
    ];
    $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_default_value);
        $example_list_q = $query->execute();
        if ($example_list_q) {
            $example_files_rows = [];
            while ($example_list_data = $example_list_q->fetchObject()) {
                $example_file_type = '';
                switch ($example_list_data->filetype) {
                    case 'S':
                        $example_file_type = 'Source or Main file';
                        break;
                    case 'R':
                        $example_file_type = 'Result file';
                        break;
                    case 'X':
                        $example_file_type = 'xcos file';
                        break;
                    default:
                        $example_file_type = 'Unknown';
                        break;
                }
                $items[] = [
                  Link::fromTextAndUrl($example_list_data->filename, Url::fromUri('internal:/textbook-companion/download/file/' . $example_list_data->id))->toString(),
                    "{$example_file_type}"
                ];
            }
        }
            array_push($example_files_rows, $items);
            //var_dump($example_files_rows);
            /* creating list of files table */
           $form['download_example']['example_files'] = [
        '#type' => 'fieldset',
        '#title' => t('List of example files'),
      ];
      $example_files_header = ['Filename', 'Type']; // Table headers

      $table = [
        '#type' => 'table',
        '#header' => $example_files_header,
        '#rows' => $example_files_rows,
      
      '#attributes' => [
        'style' => 'width: 100%;',
        
      ],
    ];
          // Add the table to the fieldset
$form['download_example']['example_files']['table'] = $table;
    $form['download_example']['example_files'] = [
      '#type' => 'item',
      '#markup' => '',
      '#prefix' => '<div id="ajax_example_files_list">',
      '#suffix' => '</div>',
    ];
    
   
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
    return $form['download_chapter'];
  }
  function ajax_bulk_example_files_callback(array &$form, FormStateInterface $form_state){
    // $example_default_value = $form_state->getValue('example');
    // var_dump($example_default_value);
    return $form['download_example'];
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
function _ajax_bulk_get_examples($chapter_id = 0) {
  $book_examples = ['0' => t('Please select...')];

  try {
    $query = Database::getConnection()->select('textbook_companion_example', 'tce');
    $query->fields('tce');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $book_examples_q = $query->execute();

    foreach ($book_examples_q as $book_examples_data) {
      $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
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
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $prop_id);
        $user_query = $query->execute();
        $user_info = $user_query->fetchObject();
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
          /* email */
          // @FIXME

      $email_subject = $this->t('[@site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the book have been approved.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
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
          /* email */
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your all the uploaded examples for the book have been marked as pending to be reviewed.
          // You will be able to see the examples after they have been approved by one of our reviewers.
          // 
          // Title of the book : ' . $preference_data->book . '
          // Author name : ' . $preference_data->author . '
          // ISBN No. : ' . $preference_data->isbn . '
          // Publisher and Place : ' . $preference_data->publisher . '
          // Edition : ' . $preference_data->edition . '
          // Year of publication : ' . $preference_data->year . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          /* email */
          /*$email_subject = t('Your uploaded examples have been marked as dis-approved');
                $email_body =array( t('Your all the uploaded examples for the whole book have been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as
          // 				dis-approved', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your all the uploaded examples for the whole book have been marked as dis-approved.
          // 
          // Title of the book : ' . $preference_data->book . '
          // Author name : ' . $preference_data->author . '
          // ISBN No. : ' . $preference_data->isbn . '
          // Publisher and Place : ' . $preference_data->publisher . '
          // Edition : ' . $preference_data->edition . '
          // Year of publication : ' . $preference_data->year . '
          // 
          // 
          // Reason for dis-approval:' . $form_state['values']['message'] . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_subject = t('[!site_name] Your uploaded Textbook Companion examples including the book proposal 					have been deleted', array(
            //                         '!site_name' => variable_get('site_name', '')
            //                     ));

            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_body = array(
            //                         0 => t('
            // 
            // Dear !user_name,
            // 
            // We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.
            // 
            // 
            // Title of the book : ' . $pref_data->book . '
            // Author name : ' . $pref_data->author . '
            // ISBN No. : ' . $pref_data->isbn . '
            // Publisher and Place : ' . $pref_data->publisher . '
            // Edition : ' . $pref_data->edition . '
            // Year of publication : ' . $pref_data->year . '
            // 
            // Reason for deletion:' . $form_state['values']['message'] . '
            // 
            // 
            // Best Wishes,
            // 
            // !site_name Team,
            // FOSSEE,IIT Bombay', array(
            //                             '!site_name' => variable_get('site_name', ''),
            //                             '!user_name' => $user_data->name
            //                         ))
            //                     );

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
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been approved', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your all the uploaded examples for the chapter have been approved.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your all the uploaded examples for the chapter have been marked as pending to be reviewed.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been marked as 					dis-approved', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your uploaded example for the entire chapter have been marked as dis-approved.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // 
          // 
          // Reason for dis-approval:' . $form_state['values']['message'] . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been approved', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your example for OpenModelica Textbook Companion with the following details is approved.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // Example number : ' . $examp_data->number . '
          // Caption : ' . $examp_data->caption . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as pending', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your uploaded example for OpenModelica Textbook Companion with the following details has been marked as pending to be reviewed.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // Example number : ' . $examp_data->number . '
          // Caption : ' . $examp_data->caption . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

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
          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as
          // 				dis-approved', array(
          //                     '!site_name' => variable_get('site_name', '')
          //                 ));

          // @FIXME
          // // @FIXME
          // // This looks like another module's variable. You'll need to rewrite this call
          // // to ensure that it uses the correct configuration object.
          // $email_body = array(
          //                     0 => t('
          // 
          // Dear !user_name,
          // 
          // Your example for OpenModelica Textbook Companion has been marked as dis-approved and deleted.
          // 
          // Title of the book : ' . $pref_data->book . '
          // Title of the chapter : ' . $chap_data->name . '
          // Example number : ' . $examp_data->number . '
          // Caption : ' . $examp_data->caption . '
          // 
          // Reason for dis-approval:' . $form_state['values']['message'] . '
          // 
          // Best Wishes,
          // 
          // !site_name Team,
          // FOSSEE,IIT Bombay', array(
          //                         '!site_name' => variable_get('site_name', ''),
          //                         '!user_name' => $user_data->name
          //                     ))
          //                 );

        }
        else {
          \Drupal::messenger()->addError(t('Please select only one action at a time'));
          return;
        }
        /****** sending email when everything done ******/
        if ($email_subject) {
          $email_to = $user_data->mail;
          $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
          $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
          $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
          $param['standard']['subject'] = $email_subject;
          $param['standard']['body'] = $email_body;
          $param['standard']['headers'] = [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ];
          if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, $from, TRUE)) {
            \Drupal::messenger()->addError('Error sending email message.');
          }
        }
      }
      else {
        \Drupal::messenger()->addError(t('You do not have permission to bulk manage code.'));
      }
    }
  }


?>
