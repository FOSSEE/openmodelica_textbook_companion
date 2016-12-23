<?php
function textbook_companion_download_book()
  {
    $preference_id = arg(2);
    _latex_copy_script_file();
    $full_book = arg(3);
    if ($full_book == "1")
        _latex_generate_files($preference_id, TRUE);
    else
        _latex_generate_files($preference_id, FALSE);
  }
function _latex_generate_files($preference_id, $full_book = FALSE)
  {
    $root_path = textbook_companion_path();
    $dir_path = $root_path . "latex/";
    $book_filedata = "";
    $contributor_filedata = "";
    $latex_filedata = "";
    $latex_dep_filedata = "";
    $depedency_list = array();
    $eol = "\n";
    $sep = "#";
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $preference_q = $query->execute();
    $preference_data = $preference_q->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message('Invalid book specified.', 'error');
        drupal_goto('');
      }
    if ($preference_data->approval_status == 0)
      {
        drupal_set_message('Book proposal is still in pending review.', 'error');
        drupal_goto('');
      }
    $book_filedata = $preference_data->book . $sep . $preference_data->author . $sep . $preference_data->isbn . $sep . $preference_data->publisher . $sep . $preference_data->edition . $sep . $preference_data->year . $eol;
    /* regenerate book if full book selected */
    if ($full_book)
        del_book_pdf($preference_data->id);
    /* check if book already generated */
    if (file_exists($dir_path . "book_" . $preference_data->id . ".pdf"))
      {
        /* download zip file */
        header('Content-Type: application/pdf');
        header('Content-disposition: attachment; filename="' . $preference_data->book . '_' . $preference_data->author . '.pdf"');
        header('Content-Length: ' . filesize($dir_path . "book_" . $preference_data->id . ".pdf"));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($dir_path . "book_" . $preference_data->id . ".pdf");
        return;
      }
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message('Could not fetch contributors information for the book specified.', 'error');
      }
    $contributor_filedata .= $proposal_data->full_name . $sep . $proposal_data->course . $sep . $proposal_data->branch . $sep . $proposal_data->university . $sep . $proposal_data->faculty . $sep . $proposal_data->reviewer . $eol;
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number DESC", $preference_data->id);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_data->id);
    $query->orderBy('number', 'DESC');
    $chapter_q = $query->execute();
    while ($chapter_data = $chapter_q->fetchObject())
      {
        if ($full_book)
          {
            /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d ORDER BY number DESC", $chapter_data->id);*/
            $query = db_select('textbook_companion_example');
            $query->fields('textbook_companion_example');
            $query->condition('chapter_id', $chapter_data->id);
            $query->orderBy('number', 'DESC');
            $example_q = $query->execute();
          }
        else
          {
            /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1 ORDER BY number DESC", $chapter_data->id);*/
            $query = db_select('textbook_companion_example');
            $query->fields('textbook_companion_example');
            $query->condition('chapter_id', $chapter_data->id);
            $query->condition('approval_status', 1);
            $query->orderBy('number', 'DESC');
            $example_q = $query->execute();
          }
        while ($example_data = $example_q->fetchObject())
          {
            /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_data->id);*/
            $query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_data->id);
            $example_files_q = $query->execute();
            while ($example_files_data = $example_files_q->fetchObject())
              {
                $latex_filedata .= $chapter_data->number . $sep;
                $latex_filedata .= $chapter_data->name . $sep;
                $latex_filedata .= $example_data->number . $sep;
                $latex_filedata .= $example_data->caption . $sep;
                $latex_filedata .= $example_files_data->filename . $sep;
                $latex_filedata .= $example_files_data->filepath . $sep;
                $latex_filedata .= $example_files_data->filetype . $sep;
                $latex_filedata .= $sep;
                $latex_filedata .= $example_files_data->id;
                $latex_filedata .= $eol;
              }
            /*$dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_data->id);*/
            $query = db_select('textbook_companion_example_dependency');
            $query->fields('textbook_companion_example_dependency');
            $query->condition('example_id', $example_data->id);
            $dependency_files_q = $query->execute();
            while ($dependency_files_data = $dependency_files_q->fetchObject())
              {
                /*$dependency_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $dependency_files_data->dependency_id);*/
                $query = db_select('textbook_companion_dependency_files');
                $query->fields('textbook_companion_dependency_files');
                $query->condition('id', $dependency_files_data->dependency_id);
                $query->range(0, 1);
                $dependency_q = $query->execute();
                if ($dependency_data = $dependency_q->fetchObject())
                  {
                    $latex_filedata .= $chapter_data->number . $sep;
                    $latex_filedata .= $chapter_data->name . $sep;
                    $latex_filedata .= $example_data->number . $sep;
                    $latex_filedata .= $example_data->caption . $sep;
                    $latex_filedata .= $dependency_data->filename . $sep;
                    $latex_filedata .= $dependency_data->filepath . $sep;
                    $latex_filedata .= 'D' . $sep;
                    $latex_filedata .= $dependency_data->caption . $sep;
                    $latex_filedata .= $dependency_data->id;
                    $latex_filedata .= $eol;
                    $depedency_list[$dependency_data->id] = "D";
                  }
              }
          }
      }
    foreach ($depedency_list as $row => $data)
      {
        /*$dependency_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $row);*/
        $query = db_select('textbook_companion_dependency_files');
        $query->fields('textbook_companion_dependency_files');
        $query->condition('id', $row);
        $query->range(0, 1);
        $dependency_q = $query->execute();
        if ($dependency_data = $dependency_q->fetchObject())
          {
            $latex_dep_filedata .= $dependency_data->filename . $sep;
            $latex_dep_filedata .= $dependency_data->filepath . $sep;
            $latex_dep_filedata .= $dependency_data->caption . $sep;
            $latex_dep_filedata .= $dependency_data->id;
            $latex_dep_filedata .= $eol;
          }
      }
    /**************************** WRITE TO FILES ********************************/
    $download_filename = $preference_data->book . "_" . $preference_data->author;
    $book_filename = "tmp_" . $preference_data->id . "_book.txt";
    $contributor_filename = "tmp_" . $preference_data->id . "_contributor.txt";
    $latex_filename = "tmp_" . $preference_data->id . "_data.txt";
    $latex_dep_filename = "tmp_" . $preference_data->id . "_dep_data.txt";
    $pdf_filename = "book_" . $preference_data->id . ".pdf";
    // $book_filedata = str_replace("&", "\&", $book_filedata);
    $fb = fopen($dir_path . $book_filename, 'w');
    fwrite($fb, $book_filedata);
    fclose($fb);
    // $contributor_filedata = str_replace("&", "\&", $contributor_filedata);
    $fc = fopen($dir_path . $contributor_filename, 'w');
    fwrite($fc, $contributor_filedata);
    fclose($fc);
    $fl = fopen($dir_path . $latex_filename, 'w');
    fwrite($fl, $latex_filedata);
    fclose($fl);
    $fd = fopen($dir_path . $latex_dep_filename, 'w');
    fwrite($fd, $latex_dep_filedata);
    fclose($fd);
    if (_latex_run_script($book_filename, $contributor_filename, $latex_filename, $latex_dep_filename, $pdf_filename))
      {
        /* download zip file */
        header('Content-Type: application/pdf');
        header('Content-disposition: attachment; filename="' . $preference_data->book . '_' . $preference_data->author . '.pdf"');
        header('Content-Length: ' . filesize($dir_path . $pdf_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($dir_path . $pdf_filename);
      }
    else
      {
        drupal_set_message("Error occurred when generating the PDF version of the Book.", 'error');
      }
    /*********************** DELETING TEMPORARY FILES ***************************/
    /* regenerate book if full book selected */
    if ($full_book)
        del_book_pdf($preference_data->id);
  }
function _latex_copy_script_file()
  {
    exec("cp ./" . drupal_get_path('module', 'textbook_companion') . "/latex/* ./uploads/latex");
    exec("chmod u+x ./uploads/latex/*.sh");
  }
function _latex_run_script($book_filename, $contributor_filename, $latex_filename, $latex_dep_filename, $pdf_filename)
  {
    $root_path = textbook_companion_path();
    $ret = 0;
    chdir("uploads");
    chdir("latex");
    $sh_command = "./pdf_creator.sh " . $book_filename . " " . $contributor_filename . " " . $latex_filename . " " . $latex_dep_filename;
    exec($sh_command);
    exec("mv TEX_final.pdf " . $pdf_filename);
    if ($ret == 0)
        return TRUE;
    else
        return FALSE;
  }
function textbook_companion_delete_book()
  {
    $book_id = arg(2);
    del_book_pdf($book_id);
    drupal_set_message(t('Book schedule for regeneration.'), 'status');
    drupal_goto('code_approval/bulk');
    return;
  }
