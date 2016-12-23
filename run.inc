<?php
function textbook_companion_run_form($form, &$form_state)
{
    $url_book_pref_id = (int) arg(2);
    //  var_dump($url_book_pref_id);die;
    if ($url_book_pref_id) {
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference', array(
            'category'
        ));
        $query->condition('id', $url_book_pref_id);
        $result = $query->execute()->fetchObject();
        $category_default_value = $result->category;
    } else {
        $category_default_value = 0;
    }
    if ($url_book_pref_id) {
        $form['category'] = array(
            '#type' => 'hidden',
            '#title' => t('Category'),
            '#options' => _list_of_category(),
            '#default_value' => $category_default_value,
            '#ajax' => array(
                'callback' => 'ajax_book_list_callback'
            ),
            '#validated' => TRUE
        );
        $book_default_value = $url_book_pref_id;
        $form['book'] = array(
            '#type' => 'select',
            '#title' => t('Title of the book'),
            '#options' => _list_of_books($book_default_value),
            '#default_value' => $book_default_value,
            '#prefix' => '<div id="ajax-book-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_chapter_list_callback'
            ),
            '#validated' => TRUE
        );
        /*$form['book_details'] = array(
        '#prefix' => '<div id="ajax-book-details-replace"></div>',
        '#suffix' => '</div>',
        '#markup' => '',
        );*/
        $form['book_details'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-book-details-replace">' . _html_book_info($book_default_value) . '</div>'
        );
        $form['download_book'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-book-replace">' . l('Download', 'textbook-companion/download/book/' . $book_default_value) . ' ' . t('(Download the OpenModelica codes for all the solved examples)') . '</div>'
        );
        /*$book_pref_id_array = array("19");
        if(in_array($book_default_value, $book_pref_id_array)){
        $form['freeeda_download_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-freeeda-book-replace">'.l('Download (FreeEDA Version)', 'textbook-companion/uploads/Microelectronic_Circuits___Theory_And_Applications_FreeEDA_Version.zip') . ' ' . t('(Download the FreeEDA codes for all the solved examples)').'</div>',
        );
        }*/
        /* $form['download_pdf'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace">'.l('Download PDF', 'textbook_companion/generate_book/' . $book_default_value) . ' ' . t('(Download the PDF file containing eSim codes for all the solved examples)').'</div>',
        );*/
        $form['chapter'] = array(
            '#type' => 'select',
            '#title' => t('Title of the chapter'),
            '#options' => _list_of_chapters($book_default_value),
            //'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
            '#prefix' => '<div id="ajax-chapter-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_example_list_callback'
            ),
            '#validated' => TRUE,
           /* '#states' => array(
                'invisible' => array(
                    ':input[name="category"]' => array(
                        'value' => 0
                    )
                )
            )*/
        );
        $form['download_chapter'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-chapter-replace"></div>'
        );
        $example_default_value = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '';
        $form['examples'] = array(
            '#type' => 'select',
            '#title' => t('Example No. (Caption): '),
            '#options' => _list_of_examples($example_default_value),
            '#default_value' => isset($form_state['values']['examples']) ? $form_state['values']['examples'] : '',
            '#prefix' => '<div id="ajax-example-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_example_files_callback'
            ),
            '#states' => array(
                'invisible' => array(
                    ':input[name="chapter"]' => array(
                        'value' => 0
                    )
                )
            )
        );
        $form['download_example_code'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-example-code-replace"></div>'
        );
        $form['example_files'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-example-files-replace"></div>'
        );
    } else {
        $form['category'] = array(
            '#type' => 'hidden',
            '#title' => t('Category'),
            '#options' => _list_of_category(),
            '#default_value' => $category_default_value,
            '#ajax' => array(
                'callback' => 'ajax_book_list_callback'
            ),
            '#validated' => TRUE
        );
        $form['book'] = array(
            '#type' => 'select',
            '#title' => t('Title of the book'),
            '#options' => _list_of_books(),
            //'#default_value' => isset($form_state['values']['book']) ? $form_state['values']['book'] : 0,
            '#prefix' => '<div id="ajax-book-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_chapter_list_callback'
            ),
            '#validated' => TRUE
            //'#states' => array('invisible' => array(':input[name="category"]' => array('value' => 0),),),  
        );
        $form['book_details'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-book-details-replace"></div>'
        );
        $form['download_book'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-book-replace"></div>'
        );
        $form['freeeda_download_book'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-freeeda-book-replace"></div>'
        );
        /* $form['download_pdf'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace"></div>',
        );*/
        $book_default_value = isset($form_state['values']['book']) ? $form_state['values']['book'] : '';
        $form['chapter'] = array(
            '#type' => 'select',
            '#title' => t('Title of the chapter'),
            '#options' => _list_of_chapters($book_default_value),
            //'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
            '#prefix' => '<div id="ajax-chapter-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_example_list_callback'
            ),
            '#validated' => TRUE,
            '#states' => array(
                'invisible' => array(
                    ':input[name="book"]' => array(
                        'value' => 0
                    )
                )
            )
        );
        $form['download_chapter'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-chapter-replace"></div>'
        );
        $example_default_value = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '';
        $form['examples'] = array(
            '#type' => 'select',
            '#title' => t('Example No. (Caption): '),
            '#options' => _list_of_examples($example_default_value),
            '#default_value' => isset($form_state['values']['examples']) ? $form_state['values']['examples'] : '',
            '#prefix' => '<div id="ajax-example-list-replace">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => 'ajax_example_files_callback'
            ),
            '#states' => array(
                'invisible' => array(
                    ':input[name="book"]' => array(
                        'value' => 0
                    )
                )
            )
        );
        $form['download_example_code'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-example-code-replace"></div>'
        );
        $form['example_files'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax-download-example-files-replace"></div>'
        );
    }
    return $form;
}
/********************* Ajax callback ***************************/
function ajax_book_list_callback($form, $form_state)
{
    $category_default_value = $form_state['values']['category'];
    if ($category_default_value == 0) {
   
        $form['book']['#options'] = _list_of_books($category_default_value);
        $commands[] = ajax_command_replace("#ajax-book-list-replace", drupal_render($form['book']));
        $commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
        $commands[] = ajax_command_html("#ajax-example-list-replace", '');
    } else {
    	 
        $form['book']['#options'] = _list_of_books();
        $commands[] = ajax_command_replace("#ajax-book-list-replace", drupal_render($form['book']));
        $commands[] = ajax_command_html("#ajax-book-list-replace", '');
        $commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
        $commands[] = ajax_command_html("#ajax-example-list-replace", '');
        $commands[] = ajax_command_html("#ajax-book-details-replace", '');
        $commands[] = ajax_command_html("#ajax-download-book-replace", '');
        $commands[] = ajax_command_html("#ajax-download-freeeda-book-replace", '');
        $commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
/*************************************************************************/
function ajax_chapter_list_callback($form, $form_state)
{
    $book_list_default_value = $form_state['values']['book'];
    if ($book_list_default_value > 0) {
        $commands[] = ajax_command_html("#ajax-book-details-replace", _html_book_info($book_list_default_value));
        $form['chapter']['#options'] = _list_of_chapters($book_list_default_value);
        $commands[] = ajax_command_html("#ajax-download-book-replace", l('Download', 'textbook-companion/download/book/' . $book_list_default_value) . ' ' . t('(Download the OpenModelica codes for all the solved examples)'));
        $book_pref_id_array = array(
            "19"
        );
        if (in_array($book_list_default_value, $book_pref_id_array)) {
            $commands[] = ajax_command_html("#ajax-download-freeeda-book-replace", l('Download FreeEDA Version', 'textbook-companion/uploads/Microelectronic_Circuits___Theory_And_Applications_FreeEDA_Version.zip') . ' ' . t('(Download the FreeEDA codes for all the solved examples)'));
        } else {
            $commands[] = ajax_command_html("#ajax-download-freeeda-book-replace", '');
        }
        $commands[] = ajax_command_replace("#ajax-chapter-list-replace", drupal_render($form['chapter']));
        $commands[] = ajax_command_html("#ajax-example-list-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
        $commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
    } else {
        $commands[] = ajax_command_html("#ajax-book-details-replace", '');
        $form['chapter']['#options'] = _list_of_chapters();
        $commands[] = ajax_command_replace("#ajax-chapter-list-replace", drupal_render($form['chapter']));
        $commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
        $commands[] = ajax_command_html("#ajax-download-book-replace", '');
        $commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
        $commands[] = ajax_command_html("#ajax-download-freeeda-book-replace", '');
        $commands[] = ajax_command_html("#ajax-example-list-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_example_list_callback($form, $form_state)
{
    $chapter_list_default_value = $form_state['values']['chapter'];
    if ($chapter_list_default_value > 0) {
        $form['examples']['#options'] = _list_of_examples($chapter_list_default_value);
        $commands[] = ajax_command_replace("#ajax-example-list-replace", drupal_render($form['examples']));
        $commands[] = ajax_command_html("#ajax-download-chapter-replace", l('Download', 'textbook-companion/download/chapter/' . $chapter_list_default_value) . ' ' . t('(Download the OpenModelica codes for all the solved examples from the Chapter)'));
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
    } else {
        $form['examples']['#options'] = _list_of_examples();
        $commands[] = ajax_command_replace("#ajax-example-list-replace", drupal_render($form['examples']));
        $commands[] = ajax_command_html("#ajax-example-list-replace", '');
        $commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
/*****************************************************/
function ajax_example_files_callback($form, $form_state)
{
    $example_list_default_value = $form_state['values']['examples'];
    if ($example_list_default_value != 0) {
        //  $example_list_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $form_state['values']['run']['example']);
        $query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_list_default_value);
        $example_list_q = $query->execute();
        if ($example_list_q) {
            $example_files_rows = array();
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
                $example_files_rows[] = array(
                    l($example_list_data->filename, 'textbook-companion/download/file/' . $example_list_data->id),
                    $example_file_type
                );
            }
            /* creating list of files table */
            $example_files_header = array(
                'Filename',
                'Type'
            );
            $example_files = theme('table', array(
                'header' => $example_files_header,
                'rows' => $example_files_rows
            ));
        }
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", l('Download OpenModelica code for the example', 'textbook-companion/download/example/' . $example_list_default_value));
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", $example_files);
    } else {
        $commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
        $commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
/*******************************************************************/
function bootstrap_table_format($headers, $rows)
{
    $thead = "";
    $tbody = "";
    foreach ($headers as $header) {
        $thead .= "<th>{$header}</th>";
    }
    foreach ($rows as $row) {
        $tbody .= "<tr>";
        foreach ($row as $data) {
            $tbody .= "<td>{$data}</td>";
        }
        $tbody .= "</tr>";
    }
    $table = "
            <table class='table table-bordered table-hover' style='margin-left:-140px'>
                <thead>{$thead}</thead>
                <tbody>{$tbody}</tbody>
            </table>
        ";
    return $table;
}
/***********************************************************************************/
function _list_of_category()
{
    $category_titles = array(
        0 => 'Please select category ...',
        1 => 'Fluid Mechanics',
        2 => 'Control Theory & Control Systems',
        3 => 'Chemical Engineering',
        4 => 'Thermodynamics',
        5 => 'Mechanical Engineering',
        6 => 'Signal Processing',
        7 => 'Digital Communications',
        8 => 'Electrical Technology',
        9 => 'Mathematics & Pure Science',
        10 => 'Analog Electronics',
        11 => 'Digital Electronics',
        12 => 'Computer Programming',
        13 => 'Others'
    );
    return $category_titles;
}
function _list_of_books($preference_id = NULL)
{
    if ($preference_id != NULL) {
        $book_titles = array(
            0 => 'Please select ...'
        );
        // $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE category=".$category_default_value." AND approval_status = 1 OR approval_status = 3 ORDER BY book ASC");
        // $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE category=".$category_default_value." AND approval_status = 1 AND proposal_id IN (SELECT id FROM textbook_companion_proposal WHERE proposal_status=3) ORDER BY book ASC");
        // var_dump('ok= '. $category_default_value);
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $preference_id);
        $query->condition('approval_status', 1);
        $subquery = db_select('textbook_companion_proposal');
        $subquery->fields('textbook_companion_proposal', array(
            'id'
        ));
        $subquery->condition('proposal_status', 3);
        $query->condition('proposal_id', $subquery, 'IN');
        $query->orderBy('book', 'ASC');
        $book_titles_q = $query->execute();
        while ($book_titles_data = $book_titles_q->fetchObject()) {
            $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
        }
    } else {
        $book_titles = array(
            0 => 'Please select ...'
        );
        // $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE category=".$category_default_value." AND approval_status = 1 OR approval_status = 3 ORDER BY book ASC");
        // $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE category=".$category_default_value." AND approval_status = 1 AND proposal_id IN (SELECT id FROM textbook_companion_proposal WHERE proposal_status=3) ORDER BY book ASC");
        // var_dump('ok= '. $category_default_value);
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        //$query->condition('id', $preference_id);
        $query->condition('approval_status', 1);
        $subquery = db_select('textbook_companion_proposal');
        $subquery->fields('textbook_companion_proposal', array(
            'id'
        ));
        $subquery->condition('proposal_status', 3);
        $query->condition('proposal_id', $subquery, 'IN');
        $query->orderBy('book', 'ASC');
        $book_titles_q = $query->execute();
        while ($book_titles_data = $book_titles_q->fetchObject()) {
            $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
        }
    }
    return $book_titles;
}
function _list_of_chapters($preference_id)
{
    
    
    $book_chapters = array(
        0 => 'Please select...'
    );
    //$book_chapters_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_id);
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $book_chapters_q = $query->execute();
    while ($book_chapters_data = $book_chapters_q->fetchObject()) {
        $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
    }
   
    return $book_chapters;
}
function _list_of_examples($chapter_id = 0)
{
    $book_examples = array(
        0 => 'Please select...'
    );
    //$book_examples_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1 ORDER BY
    // CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
    // CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
    //  CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    //$query->orderBy('', '');
    $book_examples_q = $query->execute();
    while ($book_examples_data = $book_examples_q->fetchObject()) {
        $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
    }
    return $book_examples;
}
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
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->condition('preference.id', $preference_id);
    $book_data = $query->execute()->fetchObject();
    return $book_data;
}
function _html_book_info($preference_id)
{
    /*$book_details = db_fetch_object(db_query("SELECT
    preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
    proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
    FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id=".$preference_id));*/
    $query = db_select('textbook_companion_proposal', 'proposal');
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
    $query->fields('proposal', array(
        'full_name',
        'faculty',
        'reviewer',
        'course',
        'branch',
        'university'
    ));
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->fields('preference', array(
        'book',
        'author',
        'isbn',
        'publisher',
        'edition',
        'year'
    ));
    $query->condition('preference.id', $preference_id);
    $book_details = $query->execute()->fetchObject();
    $html_data = '';
    if ($book_details) {
        $html_data = '<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;" valign="top">' . '<tr><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span></td><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span></td>' . '<tr><td valign="top"><ul>' . '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>' . '<li><strong>Title of the Book:</strong> ' . $book_details->preference_book . '</li>' . '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>' . '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>' . '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>' . '</ul></td><td valign="top"><ul>' . '<li><strong>Contributor Name: </strong>' . $book_details->proposal_full_name . ', ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>' . '<li><strong>College Teacher: </strong>' . $book_details->proposal_faculty . '</li>' . '<li><strong>Reviewer: </strong>' . $book_details->proposal_reviewer . '</li>' . '</ul></td></tr>' . '</table>';
    }
    return $html_data;
}
