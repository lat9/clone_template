<?php
// -----
// Part of the "Clone Template" encapsulated plugin for Zen Cart v1.5.8 or later.
//
// Last updated: v2.0.0
//
// Copyright (c) 2016-2023, Vinos de Frutas Tropicales (lat9)
//
//namespace Zencart\Plugins\Admin\CloneTemplate;

class cloneTemplate extends base
{
    protected
        $source_template,
        $target_template,
        $logfile_name,
        $logs;

    public function __construct($source_template, $target_template = false)
    {
        $this->source_template = $source_template;
        $this->target_template = $target_template;
        if ($target_template === false) {
            $this->logfile_name = DIR_FS_LOGS . '/remove_template_' . rtrim($source_template, DIRECTORY_SEPARATOR) . '_' . date('Ymd_His') . '.log';
        } else {
            $this->logfile_name = DIR_FS_LOGS . '/clone_template_' . rtrim($source_template, DIRECTORY_SEPARATOR) . '_to_' . rtrim($target_template, DIRECTORY_SEPARATOR) . '_' . date ('Ymd_His') . '.log';
        }
    }

    public function getLogFileName()
    {
        return $this->logfile_name;
    }

    public function copyDirectoryFiles($source_folder)
    {
        $this->logs = [];
        $files = $this->getDirectoryFilesRecursive($source_folder);
        $this->logProgress(sprintf(LOG_FOLDER_FILES_FOUND, count($files), $source_folder), 'heading');
        foreach ($files as $source_file) {
            $target_file = $this->strReplaceLast($this->source_template, $this->target_template, $source_file);
            $target_directory = pathinfo($target_file, PATHINFO_DIRNAME);
            if (!is_dir($target_directory)) {
                mkdir($target_directory, 0777, true);
                $this->logProgress(sprintf(LOG_FOLDER_CREATED, $target_directory));
            }
            copy($source_file, $target_file);
            $this->logProgress(sprintf(LOG_COPYING_FILE, $source_file, $target_file));
        }
        return $this->logs;
    }

    protected function strReplaceLast($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    public function removeDirectoryFiles($source_folder)
    {
        $this->logs = [];
        $files = $this->getDirectoryFilesRecursive($source_folder);
        $this->logProgress(sprintf(LOG_FOLDER_REMOVE_FILES_FOUND, count($files), $source_folder), 'heading');
        foreach ($files as $source_file) {
            chmod($source_file, 0777);
            if (is_dir($source_file)) {
                rmdir($source_file);
                $this->logProgress(sprintf(LOG_FOLDER_REMOVED, $source_file), 'heading');
            } else {
                unlink($source_file);
                $this->logProgress(sprintf(LOG_REMOVING_FILE, $source_file));
            }
        }
        if (is_dir($source_folder)) {
            chmod($source_folder, 0777);
            rmdir($source_folder);
            $this->logProgress(sprintf(LOG_FOLDER_REMOVED, $source_folder));
        }
        return $this->logs;
    }

    protected function getDirectoryFilesRecursive ($file_path, $files_array = [])
    {
        $files = glob($file_path . '{,.}*', GLOB_BRACE + GLOB_MARK);
        foreach ($files as $current_file) {
            if (substr($current_file, -1) === DIRECTORY_SEPARATOR) {
                if (!(substr($current_file, -2, 1) === '.' || substr($current_file, -3, 2) === '..')) {
                    $files_array = $this->getDirectoryFilesRecursive($current_file, $files_array);
                    if ($this->target_template === false) {
                        $files_array[] = $current_file;
                    }
                }
            } else {
                $files_array[] = $current_file;
            }
        }
        return $files_array;
    }

    protected function logProgress($message, $class = 'line-item')
    {
        $message .= "\n";
        $this->logs[$message] = $class;
        error_log(str_replace('&nbsp;', ' ', $message), 3, $this->logfile_name);
    }
}
