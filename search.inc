<?php
// $Id$
function textbook_companion_search_form($form, $form_state)
  {
    $form['#redirect'] = FALSE;
    $form['search'] = array(
        '#type' => 'textfield',
        '#title' => t('Search'),
        '#size' => 48
    );
    $form['search_by_title'] = array(
        '#type' => 'checkbox',
        '#default_value' => TRUE,
        '#title' => t('Search by Title of the Book')
    );
    $form['search_by_author'] = array(
        '#type' => 'checkbox',
        '#default_value' => TRUE,
        '#title' => t('Search by Author of the Book')
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Search')
    );
    $form['cancel'] = array(
        '#type' => 'item',
        '#markup' => l(t('Cancel'), '')
    );
    if ($_POST)
      {
        $output = '';
        $search_rows = array();
        $search_query = '';
        if ($_POST['search_by_title'] && $_POST['search_by_author'])
          {
            /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND (book LIKE '%%%s%%' OR author LIKE '%%%s%%')", $_POST['search'], $_POST['search']);*/
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('approval_status', 1);
            $or = db_or();
            $or->condition('book', '%%' . $_POST['search'] . '%%', 'LIKE');
            $or->condition('author', '%%' . $_POST['search'] . '%%', 'LIKE');
            $query->condition($or);
            $search_q = $query->execute();
          }
        else if ($_POST['search_by_title'])
          {
            /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND book LIKE '%%%s%%'", $_POST['search']);*/
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('approval_status', 1);
            $query->condition('book', '%%' . $_POST['search'] . '%%', 'LIKE');
            $search_q = $query->execute();
          }
        else if ($_POST['search_by_author'])
          {
            /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND author LIKE '%%%s%%'", $_POST['search']);*/
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('approval_status', 1);
            $query->condition('author', '%%' . $_POST['search'] . '%%', 'LIKE');
            $search_q = $query->execute();
          }
        else
          {
            drupal_set_message('Please select whether to search by Title and/or Author of the Book.', 'error');
          }
        while ($search_data = $search_q->fetchObject())
          {
            $search_rows[] = array(
                l($search_data->book, 'textbook_run/' . $search_data->id),
                $search_data->author
            );
          }
        if ($search_rows)
          {
            $search_header = array(
                'Title of the Book',
                'Author Name'
            );
            $output .= theme('table', array(
                'headers' => $search_header,
                'rows' => $search_rows
            ));
            $form['search_results'] = array(
                '#type' => 'item',
                '#title' => t('Search results for "') . $_POST['search'] . '"',
                '#markup' => $output
            );
          }
        else
          {
            $form['search_results'] = array(
                '#type' => 'item',
                '#title' => t('Search results for "') . $_POST['search'] . '"',
                '#markup' => 'No results found'
            );
          }
      }
    return $form;
  }
/******************************************************************************/
/**************************** BROWSE BY FORMS *********************************/
/******************************************************************************/
function textbook_companion_browse_book()
  {
    $return_html = _browse_list('book');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character)
      {
        /* all books */
        $return_html .= "Please select the starting character of the title of the book";
        return $return_html;
      }
    $book_rows = array();
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE book like '%s%%' AND approval_status = 1", $query_character);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('book', '' . $query_character . '%%', 'like');
    $query->condition('approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject())
      {
        $book_rows[] = array(
            l($book_data->book, 'textbook_run/' . $book_data->id),
            $book_data->author
        );
      }
    if (!$book_rows)
      {
        $return_html .= "Sorry no books are available with that title";
      }
    else
      {
        $book_header = array(
            'Title of the Book',
            'Author Name'
        );
        $return_html .= theme('table', array(
            'headers' => $book_header,
            'rows' => $book_rows
        ));
      }
    return $return_html;
  }
function textbook_companion_browse_author()
  {
    $return_html = _browse_list('author');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character)
      {
        /* all books */
        $return_html .= "Please select the starting character of the author's name";
        return $return_html;
      }
    $book_rows = array();
    /*$book_q = db_query("SELECT pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as id FROM {textbook_companion_preference} pe RIGHT JOIN  {textbook_companion_proposal} po on pe.proposal_id=po.id  WHERE po.proposal_status=3 and pe.approval_status = 1", $query_character);*/
    $query = db_select('textbook_companion_preference', 'pe');
    $query->fields('pe', array(
        'book',
        'author',
        'publisher',
        'year',
        'id'
    ));
    $query->rightJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject())
      {
        /* Initial's fix algorithm */
        preg_match_all("/{$query_character}[a-z]+/", $book_data->author, $matches);
        if (count($matches) > 0)
          {
            /* Remove the word "And"/i from the match array and make match bold */
            if (count($matches[0]) > 0)
              {
                foreach ($matches[0] as $key => $value)
                  {
                    if (strtolower($value) == "and")
                      {
                        unset($matches[$key]);
                      }
                    else
                      {
                        $matches[0][$key] = "<b>" . $value . "</b>";
                        $book_data->author = str_replace($value, $matches[0][$key], $book_data->author);
                      }
                  }
              }
            /* Check count of matches after removing And */
            if (count($matches[0]) > 0)
              {
                $book_rows[] = array(
                    l($book_data->book, 'textbook_run/' . $book_data->id),
                    $book_data->author
                );
              }
          }
      }
    if (!$book_rows)
      {
        $return_html .= "Sorry no books are available with that author's name";
      }
    else
      {
        $book_header = array(
            'Title of the Book',
            'Author Name'
        );
        $return_html .= theme('table', array(
            'headers' => $book_header,
            'rows' => $book_rows
        ));
      }
    return $return_html;
  }
function textbook_companion_browse_student()
  {
    $return_html = _browse_list('student');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    //print $query_character;
    //die();
    if (!$query_character)
      {
        /* all books */
        $return_html .= "Please select the starting character of the student's name";
        return $return_html;
      }
    $book_rows = array();
    /*$student_q = db_query("
    SELECT po.full_name, pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id, po.approval_date as approval_date
    FROM textbook_companion_preference pe LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id 
    WHERE po.proposal_status = 3 AND pe.approval_status = 1 AND full_name LIKE '%s%%'
    ", $query_character);*/
    $query = db_select('textbook_companion_preference', 'pe');
    $query->fields('po', array(
        'full_name',
        'approval_date'
    ));
    $query->fields('pe', array(
        'book',
        'author',
        'publisher',
        'year',
        'id'
    ));
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->condition('full_name', '' . $query_character . '%%', 'LIKE');
    $student_q = $query->execute();
    while ($student_data = $student_q->fetchObject())
      {
        $book_rows[] = array(
            l($student_data->book, 'textbook_run/' . $student_data->pe_id),
            $student_data->full_name
        );
      }
    if (!$book_rows)
      {
        $return_html .= "Sorry no books are available with that student's name";
      }
    else
      {
        $book_header = array(
            'Title of the Book',
            'Student Name'
        );
        $return_html .= theme('table', array(
            'headers' => $book_header,
            'rows' => $book_rows
        ));
      }
    return $return_html;
  }
function _browse_list($type)
  {
    $return_html = '';
    $char_list = array(
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z'
    );
    foreach ($char_list as $char_name)
      {
        $return_html .= l($char_name, 'textbook_search/' . $type . '/' . $char_name);
        if ($char_name != 'Z')
            $return_html .= ' | ';
      }
    return '<div id="filter-links">' . $return_html . "</div>";
  }
function _list_of_colleges()
  {
    $college_names = array(
        '0' => '--- select ---'
    );
    /*$college_names_q = db_query("SELECT DISTINCT university FROM {textbook_companion_proposal} WHERE proposal_status=1 OR proposal_status=3 ORDER BY university ASC");*/
    $query = db_select('textbook_companion_proposal');
    $query = distinct();
    $query->fields('university', array(
        ''
    ));
    $or = db_or();
    $or->condition('proposal_status', 1);
    $or->condition('proposal_status', 3);
    $query->condition($or);
    $query->orderBy('university', 'ASC');
    $college_names_q = $query->execute();
    while ($college_names_data = $college_names_q->fetchObject())
      {
        $college_names[$college_names_data->university] = $college_names_data->university;
      }
    return $college_names;
  }
function _list_books_by_college($college)
  {
    /*$query = "SELECT pro.full_name, pro.proposal_status, pre.id as preid, pre.book, pre.isbn FROM textbook_companion_proposal pro, textbook_companion_preference pre WHERE pro.university='".$college."' AND (pro.proposal_status=1 OR pro.proposal_status=3) AND pre.proposal_id=pro.id AND pre.approval_status=1";
    $result = db_query($query);*/
    $query = db_select('textbook_companion_proposal', 'pro');
    $query->fields('pro', array(
        'full_name',
        'proposal_status'
    ));
    $query->fields('pre', array(
        'id',
        'book',
        'isbn'
    ));
    $query->condition('pro.university', $college);
    $or = db_or();
    $or->condition('pro.proposal_status', 1);
    $or->condition('pro.proposal_status', 3);
    $query->condition($or);
    $query->condition('pre.proposal_id', 'pro.id');
    $query->condition('pre.approval_status', 1);
    $result = $query->execute();
    $output = '<table><tr><th>SNO</th><th>Name</th><th>Book</th><th>ISBN</th><th>Status</th></tr>';
    $sno = 1;
    while ($row = $result->fetchObject())
      {
        if ($row->proposal_status == 1)
          {
            $output .= '<tr><td>' . $sno++ . '</td><td>' . $row->full_name . '</td><td>' . $row->book . '</td><td>' . str_replace("-", "", $row->isbn) . '</td><td style="color: orange;">Approved</td>';
          }
        else
          {
            $output .= '<tr><td>' . $sno++ . '</td><td>' . $row->full_name . '</td><td><a target="_blank" href="' . base_path() . 'textbook_run/' . $row->preid . '">' . $row->book . '</a></td><td>' . str_replace("-", "", $row->isbn) . '</td><td style="color: green;">Completed</td>';
          }
        $output .= '</tr>';
      }
    return $output;
  }
function textbook_companion_browse_college_form($form_state)
  {
    $form = array();
    ahah_helper_register($form, $form_state);
    if (!isset($form_state['storage']['college_info']['college']))
      {
        $usage_default_value = '0';
      }
    else
      {
        $usage_default_value = $form_state['storage']['college_info']['college'];
      }
    $form['college_info'] = array(
        '#type' => 'fieldset',
        '#prefix' => '<div id="college-info-wrapper">',
        '#suffix' => '</div>',
        '#tree' => TRUE
    );
    $form['college_info']['college'] = array(
        '#type' => 'select',
        '#title' => t('College Name'),
        '#options' => _list_of_colleges(),
        '#default_value' => $usage_default_value,
        '#ahah' => array(
            'event' => 'change',
            'path' => ahah_helper_path(array(
                'college_info'
            )),
            'wrapper' => 'college-info-wrapper'
        )
    );
    if ($usage_default_value != '0')
      {
        $form['college_info']['book_details'] = array(
            '#type' => 'item',
            '#value' => _list_books_by_college($usage_default_value)
        );
      }
    return $form;
  }
function textbook_companion_browse_college_form_validate($form, &$form_state)
  {
  }
function textbook_companion_browse_college_form_submit($form, &$form_state)
  {
  }
