<?php

/**
 * Class ModelBillmatePaymentModificator
 */
class ModelBillmatePaymentModificator extends Model
{
    const MODIFICATION_FILE = 'bm_modification.xml';

    const MODIFICATION_CODE = 'billmate_checkout';

    const STATUS_ENABLED = 1;

    /**
     * ModelBillmatePaymentBmsetup constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/payment/modificator');
    }

    /**
     *
     */
    public function addModifications()
    {
        $modData = $this->getModificationData();
        if ($modData) {
            $this->model_setting_modification->addModification($modData);
            $this->refresh();
        }
    }

    public function deleteModifications()
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "modification` WHERE `code` = '" . self::MODIFICATION_CODE . "'"
        );
        $this->refresh();
    }

    /**
     * @return array
     */
    protected function getModificationData()
    {
        $modData = [];
        $file = DIR_SYSTEM .  self::MODIFICATION_FILE;
        if (is_file($file)) {
            $xml = file_get_contents($file);
            if ($xml) {
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->loadXml($xml);
                $name = $dom->getElementsByTagName('name')->item(0)->nodeValue;
                $author = $dom->getElementsByTagName('author')->item(0)->nodeValue;
                $version = $dom->getElementsByTagName('version')->item(0)->nodeValue;
                $link = $dom->getElementsByTagName('link')->item(0)->nodeValue;

                $modData = [
                    'extension_install_id' => 0,
                    'name'                 => $name,
                    'code'                 => self::MODIFICATION_CODE,
                    'author'               => $author,
                    'version'              => $version,
                    'link'                 => $link,
                    'xml'                  => $xml,
                    'status'               => self::STATUS_ENABLED
                ];
            }
        }
        return $modData;
    }

    /**
     * @Note This function copies functionality from the native OC ControllerMarketplaceModification
     * We must be care with future updates (will be waiting when OC separate logic on separated functions)
     */
    public function refresh()
    {
        $this->load->model('setting/modification');
            // Just before files are deleted, if config settings say maintenance mode is off then turn it on
            $maintenance = $this->config->get('config_maintenance');

            $this->load->model('setting/setting');

            $this->model_setting_setting->editSettingValue('config', 'config_maintenance', true);

            //Log
            $log = array();

            // Clear all modification files
            $files = array();

            // Make path into an array
            $path = array(DIR_MODIFICATION . '*');

            // While the path array is still populated keep looping through
            while (count($path) != 0) {
                $next = array_shift($path);

                foreach (glob($next) as $file) {
                    // If directory add to path array
                    if (is_dir($file)) {
                        $path[] = $file . '/*';
                    }

                    // Add the file to the files to be deleted array
                    $files[] = $file;
                }
            }

            // Reverse sort the file array
            rsort($files);

            // Clear all modification files
            foreach ($files as $file) {
                if ($file != DIR_MODIFICATION . 'index.html') {
                    // If file just delete
                    if (is_file($file)) {
                        unlink($file);

                        // If directory use the remove directory function
                    } elseif (is_dir($file)) {
                        rmdir($file);
                    }
                }
            }

            // Begin
            $xml = array();

            // Load the default modification XML
            $xml[] = file_get_contents(DIR_SYSTEM . 'modification.xml');

            // This is purly for developers so they can run mods directly and have them run without upload after each change.
            $files = glob(DIR_SYSTEM . '*.ocmod.xml');

            if ($files) {
                foreach ($files as $file) {
                    $xml[] = file_get_contents($file);
                }
            }

            // Get the default modification file
            $results = $this->model_setting_modification->getModifications();

            foreach ($results as $result) {
                if ($result['status']) {
                    $xml[] = $result['xml'];
                }
            }

            $modification = array();

            foreach ($xml as $xml) {
                if (empty($xml)){
                    continue;
                }

                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->loadXml($xml);

                // Log
                $log[] = 'MOD: ' . $dom->getElementsByTagName('name')->item(0)->textContent;

                // Wipe the past modification store in the backup array
                $recovery = array();

                // Set the a recovery of the modification code in case we need to use it if an abort attribute is used.
                if (isset($modification)) {
                    $recovery = $modification;
                }

                $files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

                foreach ($files as $file) {
                    $operations = $file->getElementsByTagName('operation');

                    $files = explode('|', $file->getAttribute('path'));

                    foreach ($files as $file) {
                        $path = '';

                        // Get the full path of the files that are going to be used for modification
                        if ((substr($file, 0, 7) == 'catalog')) {
                            $path = DIR_CATALOG . substr($file, 8);
                        }

                        if ((substr($file, 0, 5) == 'admin')) {
                            $path = DIR_APPLICATION . substr($file, 6);
                        }

                        if ((substr($file, 0, 6) == 'system')) {
                            $path = DIR_SYSTEM . substr($file, 7);
                        }

                        if ($path) {
                            $files = glob($path, GLOB_BRACE);

                            if ($files) {
                                foreach ($files as $file) {
                                    // Get the key to be used for the modification cache filename.
                                    if (substr($file, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                                        $key = 'catalog/' . substr($file, strlen(DIR_CATALOG));
                                    }

                                    if (substr($file, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                                        $key = 'admin/' . substr($file, strlen(DIR_APPLICATION));
                                    }

                                    if (substr($file, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                                        $key = 'system/' . substr($file, strlen(DIR_SYSTEM));
                                    }

                                    // If file contents is not already in the modification array we need to load it.
                                    if (!isset($modification[$key])) {
                                        $content = file_get_contents($file);

                                        $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                                        $original[$key] = preg_replace('~\r?\n~', "\n", $content);
                                    }

                                    foreach ($operations as $operation) {
                                        $error = $operation->getAttribute('error');

                                        // Ignoreif
                                        $ignoreif = $operation->getElementsByTagName('ignoreif')->item(0);

                                        if ($ignoreif) {
                                            if ($ignoreif->getAttribute('regex') != 'true') {
                                                if (strpos($modification[$key], $ignoreif->textContent) !== false) {
                                                    continue;
                                                }
                                            } else {
                                                if (preg_match($ignoreif->textContent, $modification[$key])) {
                                                    continue;
                                                }
                                            }
                                        }

                                        // Search and replace
                                        if ($operation->getElementsByTagName('search')->item(0)->getAttribute('regex') != 'true') {
                                            // Search
                                            $search = $operation->getElementsByTagName('search')->item(0)->textContent;
                                            $trim = $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
                                            $index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');

                                            // Trim line if no trim attribute is set or is set to true.
                                            if (!$trim || $trim == 'true') {
                                                $search = trim($search);
                                            }

                                            // Add
                                            $add = $operation->getElementsByTagName('add')->item(0)->textContent;
                                            $trim = $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
                                            $position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
                                            $offset = $operation->getElementsByTagName('add')->item(0)->getAttribute('offset');

                                            if ($offset == '') {
                                                $offset = 0;
                                            }

                                            // Trim line if is set to true.
                                            if ($trim == 'true') {
                                                $add = trim($add);
                                            }

                                            // Log
                                            $log[] = 'CODE: ' . $search;

                                            // Check if using indexes
                                            if ($index !== '') {
                                                $indexes = explode(',', $index);
                                            } else {
                                                $indexes = array();
                                            }

                                            // Get all the matches
                                            $i = 0;

                                            $lines = explode("\n", $modification[$key]);

                                            for ($line_id = 0; $line_id < count($lines); $line_id++) {
                                                $line = $lines[$line_id];

                                                // Status
                                                $match = false;

                                                // Check to see if the line matches the search code.
                                                if (stripos($line, $search) !== false) {
                                                    // If indexes are not used then just set the found status to true.
                                                    if (!$indexes) {
                                                        $match = true;
                                                    } elseif (in_array($i, $indexes)) {
                                                        $match = true;
                                                    }

                                                    $i++;
                                                }

                                                // Now for replacing or adding to the matched elements
                                                if ($match) {
                                                    switch ($position) {
                                                        default:
                                                        case 'replace':
                                                            $new_lines = explode("\n", $add);

                                                            if ($offset < 0) {
                                                                array_splice($lines, $line_id + $offset, abs($offset) + 1, array(str_replace($search, $add, $line)));

                                                                $line_id -= $offset;
                                                            } else {
                                                                array_splice($lines, $line_id, $offset + 1, array(str_replace($search, $add, $line)));
                                                            }
                                                            break;
                                                        case 'before':
                                                            $new_lines = explode("\n", $add);

                                                            array_splice($lines, $line_id - $offset, 0, $new_lines);

                                                            $line_id += count($new_lines);
                                                            break;
                                                        case 'after':
                                                            $new_lines = explode("\n", $add);

                                                            array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);

                                                            $line_id += count($new_lines);
                                                            break;
                                                    }
                                                }
                                            }

                                            $modification[$key] = implode("\n", $lines);
                                        } else {
                                            $search = trim($operation->getElementsByTagName('search')->item(0)->textContent);
                                            $limit = $operation->getElementsByTagName('search')->item(0)->getAttribute('limit');
                                            $replace = trim($operation->getElementsByTagName('add')->item(0)->textContent);

                                            // Limit
                                            if (!$limit) {
                                                $limit = -1;
                                            }

                                            // Log
                                            $match = array();

                                            preg_match_all($search, $modification[$key], $match, PREG_OFFSET_CAPTURE);

                                            // Remove part of the the result if a limit is set.
                                            if ($limit > 0) {
                                                $match[0] = array_slice($match[0], 0, $limit);
                                            }

                                            // Make the modification
                                            $modification[$key] = preg_replace($search, $replace, $modification[$key], $limit);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($modification as $key => $value) {
                // Only create a file if there are changes
                if ($original[$key] != $value) {
                    $path = '';

                    $directories = explode('/', dirname($key));

                    foreach ($directories as $directory) {
                        $path = $path . '/' . $directory;

                        if (!is_dir(DIR_MODIFICATION . $path)) {
                            @mkdir(DIR_MODIFICATION . $path, 0777);
                        }
                    }

                    $handle = fopen(DIR_MODIFICATION . $key, 'w');

                    fwrite($handle, $value);

                    fclose($handle);
                }
            }

            // Maintance mode back to original settings
            $this->model_setting_setting->editSettingValue('config', 'config_maintenance', $maintenance);
    }
}