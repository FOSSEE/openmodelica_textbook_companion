<?php
// $Id$
function textbook_companion_download_example_file()
  {
    $example_file_id = arg(3);
    $root_path = textbook_companion_path();
    $root_temp_path = textbook_companion_temp_path();
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE id = %d LIMIT 1", $example_file_id);
    $example_file_data = db_fetch_object($example_files_q);*/
    /*$query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('id', $example_file_id);
    $query->range(0, 1);
    $result = $query->execute();*/
    $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.id= :example_id LIMIT 1", array(
        ':example_id' => $example_file_id
    ));
    $example_file_data = $example_files_q->fetchObject();
    header('Content-Type: ' . $example_file_data->filemime);
    header('Content-disposition: attachment; filename="' . $example_file_data->filename . '"');
    header('Content-Length: ' . filesize($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath));
    ob_clean();
    readfile($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath);
  }
function textbook_companion_download_sample_code()
  {
    $proposal_id = arg(3);
    $root_path = textbook_companion_samplecode_path();
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    $samplecodename = substr($example_file_data->samplefilepath, strrpos($example_file_data->samplefilepath, '/') + 1);
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header('Content-Length: ' . filesize($root_path . $example_file_data->samplefilepath));
    ob_clean();
    readfile($root_path . $example_file_data->samplefilepath);
  }
function textbook_companion_download_dependency_file()
  {
    $dependency_file_id = arg(3);
    $root_path = textbook_companion_path();
    /*$dependency_file_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d LIMIT 1", $dependency_file_id);
    $dependency_file_data = db_fetch_object($dependency_file_q);*/
    $query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('id', $example_file_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    header('Content-Type: ' . $dependency_file_data->filemime);
    header('Content-disposition: attachment; filename="' . $dependency_file_data->filename . '"');
    header('Content-Length: ' . filesize($root_path . $dependency_file_data->filepath));
    ob_clean();
    readfile($root_path . $dependency_file_data->filepath);
    exit;
  }
function textbook_companion_download_example()
  {
    $example_id = arg(3);
    $root_path = textbook_companion_path();
    $root_temp_path = textbook_companion_temp_path();
    /* get example data */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    /* $query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();*/
    $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", array(
        ':example_id' => $example_id
    ));
    $EX_PATH = 'EX' . $example_data->number . '/';
    /* zip filename */
    if (!is_dir($root_temp_path . 'tbc_download_temp'))
    mkdir($root_temp_path . 'tbc_download_temp');
    $zip_filename = $root_temp_path .'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive;
    $zip->open($zip_filename, ZipArchive::CREATE);
    while ($example_files_row = $example_files_q->fetchObject())
      {
        $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $EX_PATH . $example_files_row->filename);
      }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0)
      {
        /* download zip file */
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename="EX' . $example_data->number . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_clean();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    else
      {
        drupal_set_message("There are no files in this examples to download", 'error');
        drupal_goto('textbook-companion/textbook-run');
      }
  }
function textbook_companion_download_chapter()
  {
    $chapter_id = arg(3);
    //var_dump($chapter_id);die;
    $root_temp_path = textbook_companion_path();
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
if (!is_dir($root_temp_path . 'tbc_download_temp'))
    mkdir($root_temp_path . 'tbc_download_temp');
    $zip_filename = $root_temp_path .'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    
    /* creating zip archive on the server */
    $zip = new ZipArchive;
    $zip->open($zip_filename, ZipArchive::CREATE);
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
        /*$query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();*/
        $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", array(
            ':example_id' => $example_row->id
        ));
        while ($example_files_row = $example_files_q->fetchObject())
          {
            $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $CH_PATH . $EX_PATH . $example_files_row->filename);
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
        ob_clean();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    else
      {
        drupal_set_message("There are no examples in this chapter to download", 'error');
        drupal_goto('textbook-companion/textbook-run');
      }
  }
function textbook_companion_download_book()
  {
    $book_id = arg(3);
    $root_path = textbook_companion_path();
    $root_temp_path = textbook_companion_temp_path();
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $result = $query->execute();
    $book_data = $result->fetchObject();
    $zipname = str_replace(' ', '_', ($book_data->book));
    $directory_name = $book_data->directory_name;
    $BK_PATH = $zipname . '/';
    /* zip filename */
    if (!is_dir($root_temp_path . 'tbc_download_temp'))
    mkdir($root_temp_path . 'tbc_download_temp');
    $zip_filename = $root_temp_path .'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
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
            $query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();
            while ($example_files_row = $example_files_q->fetchObject())
              {
                $zip->addFile($root_path . $directory_name . '/' . $example_files_row->filepath, $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
              }
          }
      }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0)
      {
        /* download zip file */
        global $user;
        if ($user->uid)
          {
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
            header('Content-Length: ' . filesize($zip_filename));
            ob_clean();
            readfile($zip_filename);
            unlink($zip_filename);
          }
        else
          {
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
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
      }
    else
      {
        drupal_set_message("There are no examples in this book to download", 'error');
        drupal_goto('textbook-companion/textbook-run');
      }
  }
