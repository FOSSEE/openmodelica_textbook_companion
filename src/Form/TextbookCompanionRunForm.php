<?php
namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

class TextbookCompanionRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_run_form';
  }

   
   public function buildForm(array $form, FormStateInterface $form_state) {
    $url_book_pref_id = \Drupal::request()->attributes->get('book_pref_id') ?? 0;
    $category_default_value = 0;

    if ($url_book_pref_id) {
      $query = \Drupal::database()->select('textbook_companion_preference', 't');
      $query->fields('t', ['category']);
      $query->condition('id', $url_book_pref_id);
      $result = $query->execute()->fetchObject();
      $category_default_value = $result ? $result->category : 0;
    }

    // Values from form_state (AJAX) or route attribute defaults.
    $selected_book = (int) ($form_state->getValue('book') ?: $url_book_pref_id);
   // $selected_chapter = (int) $form_state->getValue('chapter');
    //$selected_example = (int) $form_state->getValue('example');

    // BOOK select (top-level)
    $form['book'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the book'),
      '#options' => $this->_list_of_books($category_default_value),
      '#default_value' => $selected_book,
      '#ajax' => [
        'callback' => '::ajax_book_changed_callback',
        'wrapper' => 'textbook-book-wrapper',
        'event' => 'change',
      ],
    ];

    // BOOK WRAPPER (contains book info, chapter select & chapter download)
    $form['book_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'textbook-book-wrapper'],
    ];

    if ($selected_book) {
      // Book info markup
      $form['book_wrapper']['book_info'] = [
        '#type' => 'markup',
        '#markup' => $this->_html_book_info($selected_book),
      ];

      // Download Book link
      $form['book_wrapper']['download_book'] = [
        '#type' => 'markup',
        '#markup' =>Link::fromTextAndUrl(
        $this->t('Download Book'),
        Url::fromUri('internal:/textbook-companion/download/book/' . $selected_book)
      )->toString() . ' ' . $this->t('(Download the OpenModelica code for all the solved examples)')
      ];
$selected_chapter = (int) $form_state->getValue('chapter');
      // Chapter select
      $form['book_wrapper']['chapter'] = [
        '#type' => 'select',
        '#title' => $this->t('Title of the chapter'),
        '#options' => $this->_list_of_chapters($selected_book),
        '#default_value' => $selected_chapter,
        '#ajax' => [
          'callback' => '::ajax_chapter_changed_callback',
          'wrapper' => 'chapter-download-wrapper',
          'event' => 'change',
        ],
      ];

      // Chapter-download wrapper (inside book_wrapper)
      $form['chapter_download'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'chapter-download-wrapper'],
      ];

      if ($selected_chapter) {
        $form['chapter_download']['link'] = [
          '#type' => 'markup',
          '#markup' => Link::fromTextAndUrl(
        $this->t('Download Chapter'),
        Url::fromUri('internal:/textbook-companion/download/chapter/' . $selected_chapter)
      )->toString() . ' ' . $this->t('(Download the OpenModelica code for all the solved examples)'),
        ];
      }
      
    
    

    $form['chapter_download']['examples'] = [
    '#type' => 'select',
    '#title' => $this->t('Name of the example'),
    '#options' => $this->_list_of_examples($selected_chapter, $selected_example),
    '#default_value' => $selected_example,
    '#ajax' => [
      'callback' => '::ajax_example_changed_callback',
      'wrapper' => 'download-example-link-wrapper',
    ],
  ];

  $selected_example = (int)$form_state->getValue('examples') ?? 0;

  // Wrapper for download example link
  $form['download_example_wrapper'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'download-example-link-wrapper'],
  ];
if($selected_example){
    $form['download_example_wrapper']['download_example'] = [
      '#type' => 'markup',
      '#markup' =>Link::fromTextAndUrl(
        $this->t('Download Example'),
        Url::fromUri('internal:/textbook-companion/download/file/' . $selected_example)
      )->toString()
    ];
  
// $form['download_example_wrapper']['example_details'] = [
//       '#type' => 'markup',
//       '#markup' => $this->t('Example no. @example', ['@example' => $selected_example]),
//     ];
//For table and example files
 $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $form_state->getValue('examples'));
        $example_list_q = $query->execute();
        if ($example_list_q)
          {
            $example_files_rows = [];
            while ($example_list_data = $example_list_q->fetchObject())
              {
                $example_file_type = '';
                switch ($example_list_data->filetype)
                {
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

                $items=[
                  Link::fromTextAndUrl($example_list_data->filename,
        Url::fromUri('internal:/textbook-companion/download/file/' . $example_list_data->example_id)
      )->toString(),
                  "{$example_file_type}"
                ];
               
              }

              array_push($example_files_rows,$items);
              $form['download_example_wrapper']['example_files'] =[
                '#type' =>'fieldset',
                '#title' => t('List of example files'),
              ];

              //$example_files_header = ['Filename','Type'];
            /* creating list of files table */
            $example_files_header = [
               'Filename',
                'Type'
            ];
               
            
            $table = [
              '#type' => 'table',
               '#header' => $example_files_header,
                '#rows' => $example_files_rows,
              '#attributes' => [
          'style' => 'width: 100%;',
              ],
          
            ];
          }
            
       
 
$form['download_example_wrapper']['example_files']['table'] = $table;
      
        }
    
    }
   

    return $form;
  }
  // ---------------------------
  // AJAX CALLBACKS
  // ---------------------------

  public function ajax_book_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['book_wrapper'];
  }

  public function ajax_chapter_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['chapter_download'];
  }
public function ajax_example_changed_callback(array &$form, FormStateInterface $form_state) {
//   $selected_example = (int) $form_state->getValue('examples') ?? 0;
//   $selected_chapter = (int) $form_state->getValue('chapter') ?? 0;
//   $form_state->setRebuild(TRUE);

// //   // Rebuild the examples select field with the correct options
//    $form['examples']['#options'] = $this->_list_of_examples($selected_chapter, $selected_example);

//   // Return the updated download example wrapper

  return $form['download_example_wrapper'];
}

  //  ---------------------------
  // HELPER FUNCTIONS
  // ---------------------------

  public function _list_of_books($category_default_value = 0) {
    $book_titles = [0 => $this->t('Please select ...')];
    $connection = \Drupal::database();

    $subquery = $connection->select('textbook_companion_proposal', 'tcp');
    $subquery->fields('tcp', ['id']);
    $subquery->condition('proposal_status', 3);

    $query = $connection->select('textbook_companion_preference', 'tcp');
    $query->fields('tcp', ['id', 'book', 'author']);
    $query->condition('category', $category_default_value);
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $subquery, 'IN');
    $query->orderBy('book', 'ASC');
    $results = $query->execute()->fetchAll();

    foreach ($results as $book) {
      $book_titles[$book->id] = $book->book . ' (Written by ' . $book->author . ')';
    }

    return $book_titles;
  }

  
  // public function ajax_example_files_callback($example_id) {
  //   if (!$example_id) {
  //       return ['#markup' => ''];
  //   }

  //   $connection = \Drupal::database();
  //   // Assuming the table name is 'textbook_companion_example_file'
  //   $query = $connection->select('textbook_companion_example_file', 'tcef');
  //   $query->fields('tcef', ['id', 'filename', 'filetype']);
  //   $query->condition('example_id', $example_id);
  //   $query->orderBy('filename', 'ASC');
  //   $results = $query->execute()->fetchAll();

  //   if (empty($results)) {
  //       return ['#markup' => ''];
  //   }

  //   $header = [
  //       'filename' => $this->t('Filename'),
  //       'filetype' => $this->t('Type'),
  //   ];

  //   $rows = [];
  //   foreach ($results as $file) {
  //       $example_file_type = $this->t('Unknown');
  //       switch ($file->filetype) {
  //           case 'S':
  //               $example_file_type = $this->t('Source or Main file');
  //               break;
  //           case 'R':
  //               $example_file_type = $this->t('Result file');
  //               break;
  //           case 'X':
  //               $example_file_type = $this->t('xcos file');
  //               break;
  //       }

  //       // Create the linked filename. Adjust the route name if necessary.
  //       $link = Link::fromTextAndUrl(
  //           $file->filename,
  //           Url::fromRoute('textbook_companion.download_file', ['file_id' => $file->id])
  //       )->toString();
        
  //       $rows[] = [
  //           // Ensure rows are simple arrays or use the 'data' structure
  //           // as necessary for your specific Drupal theme.
  //           // Using a simple array for row data is often sufficient.
  //           ['#markup' => $link],
  //           ['#markup' => $example_file_type],
  //       ];
  //   }

  //   return [
  //       '#type' => 'table',
  //       // ✅ REMOVED: Remove the '#caption' element to match the image.
  //       '#header' => $header,
  //       '#rows' => $rows,
  //       // ✅ UPDATED: Add a custom class for styling the table header.
  //       '#attributes' => ['class' => ['textbook-companion-files', 'textbook-companion-example-file-list']],
  //       '#empty' => $this->t('No files found for this example.'),
  //   ];
  // }
  public function _html_book_info($preference_id) {
    $connection = \Drupal::database();

    $query = $connection->select('textbook_companion_proposal', 'proposal');
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->addField('preference', 'book', 'preference_book');
    $query->addField('preference', 'author', 'preference_author');
    $query->addField('preference', 'isbn', 'preference_isbn');
    $query->addField('preference', 'publisher', 'preference_publisher');
    $query->addField('preference', 'edition', 'preference_edition');
    $query->addField('preference', 'year', 'preference_year');
    $query->addField('proposal', 'full_name', 'proposal_full_name');
    $query->addField('proposal', 'faculty', 'proposal_faculty');
    $query->addField('proposal', 'reviewer', 'proposal_reviewer');
    $query->addField('proposal', 'course', 'proposal_course');
    $query->addField('proposal', 'branch', 'proposal_branch');
    $query->addField('proposal', 'university', 'proposal_university');
    $query->condition('preference.id', $preference_id);

    $book_details = $query->execute()->fetchObject();
    if (!$book_details) {
      return '';
    }

    $html_data = '<table style="width:100%;" border="0">';
    $html_data .= '<tr><td style="width:50%;vertical-align:top;">';
    $html_data .= '<strong>About the Book</strong><ul>';
    $html_data .= '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>';
    $html_data .= '<li><strong>Title:</strong> ' . $book_details->preference_book . '</li>';
    $html_data .= '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>';
    $html_data .= '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>';
    $html_data .= '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>';
    $html_data .= '</ul></td><td style="width:50%;vertical-align:top;">';
    $html_data .= '<strong>About the Contributor</strong><ul>';
    $html_data .= '<li><strong>Name:</strong> ' . $book_details->proposal_full_name . '</li>';
    $html_data .= '<li><strong>Faculty:</strong> ' . $book_details->proposal_faculty . '</li>';
    $html_data .= '<li><strong>Reviewer:</strong> ' . $book_details->proposal_reviewer . '</li>';
    $html_data .= '<li><strong>Course:</strong> ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>';
    $html_data .= '</ul></td></tr></table>';

    return $html_data;
  }

  public function _list_of_chapters($preference_id = 0) {
    $book_chapters = [0 => $this->t('Please select...')];
    if (!$preference_id) return $book_chapters;

    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_chapter', 'tcc');
    $query->fields('tcc', ['id', 'name', 'number']);
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $results = $query->execute()->fetchAll();

    foreach ($results as $chapter) {
      $book_chapters[$chapter->id] = $chapter->number . '. ' . $chapter->name;
    }

    return $book_chapters;
  }

  public function _list_of_examples($chapter_id = 0, $selected_example = 0) {
  $examples = [0 => $this->t('Please select...')];
  if (!$chapter_id) {
    return $examples;
  }

  $connection = \Drupal::database();
  $query = $connection->select('textbook_companion_example', 'tce');
  $query->fields('tce', ['id', 'number', 'caption']);
  $query->condition('chapter_id', $chapter_id);
  $query->condition('approval_status', 1);
  $results = $query->execute()->fetchAll();

  foreach ($results as $example) {
    $examples[$example->id] = $example->number . '. ' . $example->caption;
  }

  // If the selected example is not in the list, add it manually
  // if ($selected_example && !isset($examples[$selected_example])) {
  //   $examples[$selected_example] = $selected_example . ' (Selected)';
  // }

  return $examples;
}                 

  
    public function submitForm(array &$form, FormStateInterface $form_state) {
    }
}