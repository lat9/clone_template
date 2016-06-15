<?php
// -----
// Part of the "Clone Template" plugin for Zen Cart v1.5.0 or later
//
// Copyright (c) 2016, Vinos de Frutas Tropicales (lat9)
//
class cloneTemplate extends base {
    public function __construct ($source_template, $target_template)
    {
        $this->source_template = $source_template;
        $this->target_template = $target_template;
        $this->logfile_name = DIR_FS_LOGS . '/clone_template_' . rtrim ($source_template, DIRECTORY_SEPARATOR) . '_to_' . rtrim ($target_template, DIRECTORY_SEPARATOR) . '_' . date ('Ymd_His') . '.log';
    }
    
    public function getLogFileName ()
    {
        return $this->logfile_name;
    }
    
    public function processDirectoryFiles ($source_folder)
    {
        $this->logs = array ();
        $files = $this->getDirectoryFilesRecursive ($source_folder);
        $this->logProgress (sprintf (LOG_FOLDER_FILES_FOUND, count ($files), $source_folder), 'heading');
        foreach ($files as $source_file) {
            $target_file = str_replace ($this->source_template, $this->target_template, $source_file);
            $target_directory = pathinfo ($target_file, PATHINFO_DIRNAME);
            if (!is_dir ($target_directory)) {
                mkdir ($target_directory, 0777, true);
                $this->logProgress (sprintf (LOG_FOLDER_CREATED, $target_directory));
            }
            copy ($source_file, $target_file);
            $this->logProgress (sprintf (LOG_COPYING_FILE, $source_file, $target_file));
        }
        return $this->logs;
    }

    protected function getDirectoryFilesRecursive ($file_path, $files_array = array ())
    {
        $files = glob ($file_path . '{,.}*', GLOB_BRACE+GLOB_MARK);
        foreach ($files as $current_file) {
            if (substr ($current_file, -1) == DIRECTORY_SEPARATOR) {
                if (!(substr ($current_file, -2, 1) == '.' || substr ($current_file, -3, 2) == '..')) {
                    $files_array = $this->getDirectoryFilesRecursive ($current_file, $files_array);
                }
            } else {
                $files_array[] = $current_file;
            }
                    
        }
        return $files_array;
    }
    
    protected function logProgress ($message, $class = 'line-item')
    {
        $message .= "\n";
        $this->logs[$message] = $class;
        error_log (str_replace ('&nbsp;', ' ', $message), 3, $this->logfile_name);
    }
}
