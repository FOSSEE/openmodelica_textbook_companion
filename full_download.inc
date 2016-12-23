<?php
// $Id$
function textbook_companion_download_full_chapter()
  {
    $chapter_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive;
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject())
      {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();
        while ($example_files_row = $example_files_q->fetchObject())
          {
            $zip->addFile($root_path . $example_files_row->filepath, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
      }
    /* unapproved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject())
      {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", array(
            ':example_id' => $example_row->id
        ));
        /*$query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject())
          {
            $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
      }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0)
      {
        /* download zip file */
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    else
      {
        drupal_set_message("There are no examples in this chapter to download", 'error');
        drupal_goto('textbook-companion/code-approval/bulk');
      }
  }
function textbook_companion_download_full_book()
  {
    $book_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $book_q = $query->execute();
    $book_data = $book_q->fetchObject();
    //$zipname = str_replace(' ','_',($book_data->book));
    //$BK_PATH = $zipname . '/';
    $BK_PATH = $book_data->book . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive;
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject())
      {
        $CH_PATH = 'CH' . $chapter_row->number . '/';
        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
        $query = db_select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('chapter_id', $chapter_row->id);
        $query->condition('approval_status', 1);
        $example_q = $query->execute();
        while ($example_row = $example_q->fetchObject())
          {
            $EX_PATH = 'EX' . $example_row->number . '/';
            /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
            $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", array(
                ':example_id' => $example_row->id
            ));
            /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
            while ($example_files_row = $example_files_q->fetchObject())
              {
                $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
              }
          }
        /* unapproved examples */
        /* $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_row->id);*/
        $query = db_select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('chapter_id', $chapter_row->id);
        $query->condition('approval_status', 0);
        $example_q = $query->execute();
        while ($example_row = $example_q->fetchObject())
          {
            $EX_PATH = 'EX' . $example_row->number . '/';
            /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
            $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", array(
                ':example_id' => $example_row->id
            ));
            /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
            while ($example_files_row = $example_files_q->fetchObject())
              {
                $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
              }
          }
      }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0)
      {
        /* download zip file */
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_clean();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    else
      {
        drupal_set_message("There are no examples in this book to download", 'error');
        drupal_goto('textbook-companion/code-approval/bulk');
      }
  }
