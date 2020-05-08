<?php

/**
 * Class ModelBillmatePaymentModificator
 */
class ModelBillmatePaymentModificator extends Model
{
    const MODIFICATION_FILE = 'bm_modification.xml';

    const MODIFICATION_CODE = 'billmate_checkout';

    const STATUS_ENABLED = 1;

    const LOG_FILE = 'ocmod.log';

    const ABORT_ERROR = 'abort';

    const SKIP_ERROR = 'skip';

    /**
     * @var Log
     */
    protected $logger;

    /**
     * @var array
     */
    protected $origModification = [];

    /**
     * @var array
     */
    protected $updateModification = [];

    /**
     * ModelBillmatePaymentBmsetup constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->model('setting/modification');
        $this->load->model('billmate/payment/modificator');
        $this->logger = new Log(self::LOG_FILE);
    }

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
        $file = DIR_SYSTEM . self::MODIFICATION_FILE;
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

    public function refresh()
    {
        $initialMaintenance = $this->config->get('config_maintenance');
        $this->updateMaintenance(true);
        try {
            $this->run();
        } catch (\Exception $exception) {
            $this->addLog(
                $exception->getMessage()
            );
        }
        $this->updateMaintenance($initialMaintenance);
    }

    protected function run()
    {
        $xmlModifiers = $this->getXmlModifiers();
        foreach ($xmlModifiers as $xml) {
            if (empty($xml)) {
                continue;
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->loadXml($xml);
            $this->addLog('MOD: ' . $dom->getElementsByTagName('name')->item(0)->textContent);

            $recovery = $this->getUpdateModification();
            $modFiles = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

            try {
                $this->loadModifications($modFiles);
            } catch (\Exception $e) {
                $this->updateModification = $recovery;
            }

            $this->addLog('----------------------------------------------------------------');
        }

        $this->writeModification();
    }

    private function loadModifications($modFiles)
    {
        foreach ($modFiles as $modFile) {
            $operations = $modFile->getElementsByTagName('operation');
            $pathFiles = explode('|', $modFile->getAttribute('path'));

            foreach ($pathFiles as $pathFile) {
                $path = $this->getFilePath($pathFile);
                if ($path) {
                    $files = glob($path, GLOB_BRACE);
                    if ($files) {
                        foreach ($files as $file) {
                            $modification = $this->getUpdateModification();
                            if (substr($file, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                                $key = 'catalog/' . substr($file, strlen(DIR_CATALOG));
                            }

                            if (substr($file, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                                $key = 'admin/' . substr($file, strlen(DIR_APPLICATION));
                            }

                            if (substr($file, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                                $key = 'system/' . substr($file, strlen(DIR_SYSTEM));
                            }

                            if (!isset($modification[$key])) {
                                $content = file_get_contents($file);
                                $this->addUpdateModification(
                                    $key,
                                    preg_replace('~\r?\n~', "\n", $content)
                                );
                                $this->addOrigModification(
                                    $key,
                                    preg_replace('~\r?\n~', "\n", $content)
                                );
                                $this->addLog(PHP_EOL . 'FILE: ' . $key);
                                $modification = $this->getUpdateModification();
                            }

                            foreach ($operations as $operation) {
                                $error = $operation->getAttribute('error');
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

                                $status = $this->updateInModificaton($operation, $key, $modification[$key]);

                                if (!$status) {
                                    if ($error == self::ABORT_ERROR) {
                                        throw new \Exception('NOT FOUND - ABORTING!');
                                    } elseif ($error == self::SKIP_ERROR) {
                                        $this->addLog('NOT FOUND - OPERATION SKIPPED!');
                                        continue;
                                    } else {
                                        $this->addLog('NOT FOUND - OPERATIONS ABORTED!');
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $operation
     * @param $mod
     *
     * @return bool
     */
    private function updateInModificaton($operation, $key, $mod)
    {
        $status = false;
        if ($operation->getElementsByTagName('search')->item(0)->getAttribute('regex') != 'true') {
            $search = $operation->getElementsByTagName('search')->item(0)->textContent;
            $trim = $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
            $index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');

            if (!$trim || $trim == 'true') {
                $search = trim($search);
            }

            $add = $operation->getElementsByTagName('add')->item(0)->textContent;
            $trim = $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
            $position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
            $offset = $operation->getElementsByTagName('add')->item(0)->getAttribute('offset');

            if ($offset == '') {
                $offset = 0;
            }

            if ($trim == 'true') {
                $add = trim($add);
            }

            $this->addLog('CODE: ' . $search);

            if ($index !== '') {
                $indexes = explode(',', $index);
            } else {
                $indexes = array();
            }

            $i = 0;

            $lines = explode("\n", $mod);

            for ($line_id = 0; $line_id < count($lines); $line_id++) {
                $line = $lines[$line_id];

                $match = false;
                if (stripos($line, $search) !== false) {
                    if (!$indexes) {
                        $match = true;
                    } elseif (in_array($i, $indexes)) {
                        $match = true;
                    }

                    $i++;
                }

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

                    $this->addLog('LINE: ' . $line_id);
                    $status = true;
                }
            }

            $this->addUpdateModification(
                $key,
                implode("\n", $lines)
            );
            return $status;
        }

        $search = trim($operation->getElementsByTagName('search')->item(0)->textContent);
        $limit = $operation->getElementsByTagName('search')->item(0)->getAttribute('limit');
        $replace = trim($operation->getElementsByTagName('add')->item(0)->textContent);

        if (!$limit) {
            $limit = -1;
        }

        $match = array();

        preg_match_all($search, $mod, $match, PREG_OFFSET_CAPTURE);
        if ($limit > 0) {
            $match[0] = array_slice($match[0], 0, $limit);
        }

        if ($match[0]) {
            $this->addLog('REGEX: ' . $search);

            $matches = count($match[0]);
            for ($i = 0; $i < $matches; $i++) {
                $this->addLog('LINE: ' . (substr_count(substr($mod, 0, $match[0][$i][1]), "\n") + 1));
            }

            $status = true;
        }

        $this->addUpdateModification(
            $key,
            preg_replace($search, $replace, $mod, $limit)
        );

        return $status;
    }

    /**
     * @return array
     */
    protected function getXmlModifiers()
    {
        $files = array();
        $path = $this->getModificationPath();
        while (count($path) != 0) {
            $next = array_shift($path);
            foreach (glob($next) as $file) {
                if (is_dir($file)) {
                    $path[] = $file . '/*';
                }
                $files[] = $file;
            }
        }

        rsort($files);

        foreach ($files as $file) {
            if ($file != DIR_MODIFICATION . 'index.html') {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    rmdir($file);
                }
            }
        }

        $xml = [];
        $xml[] = file_get_contents(DIR_SYSTEM . 'modification.xml');
        $files = glob(DIR_SYSTEM . '*.ocmod.xml');

        if ($files) {
            foreach ($files as $file) {
                $xml[] = file_get_contents($file);
            }
        }
        $results = $this->model_setting_modification->getModifications();

        foreach ($results as $result) {
            if ($result['status']) {
                $xml[] = $result['xml'];
            }
        }

        return $xml;
    }

    /**
     * @param $modification
     */
    private function writeModification()
    {
        $modification = $this->getUpdateModification();
        $original = $this->getOrigModification();

        foreach ($modification as $key => $value) {
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
    }

    /**
     * @param $pathFile
     *
     * @return string
     */
    private function getFilePath($pathFile)
    {
        if ((substr($pathFile, 0, 7) == 'catalog')) {
            return DIR_CATALOG . substr($pathFile, 8);
        }

        if ((substr($pathFile, 0, 5) == 'admin')) {
            return DIR_APPLICATION . substr($pathFile, 6);
        }

        if ((substr($pathFile, 0, 6) == 'system')) {
            return DIR_SYSTEM . substr($pathFile, 7);
        }
        return '';
    }

    /**
     * @return array
     */
    protected function getUpdateModification()
    {
        return $this->updateModification;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    protected function addUpdateModification($key, $value)
    {
        $this->updateModification[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getModificationPath()
    {
        return [
            DIR_MODIFICATION . '*'
        ];
    }

    /**
     * @return array
     */
    public function getOrigModification()
    {
        return $this->origModification;
    }

    /**
     * @param $key
     * @param $content
     *
     * @return $this
     */
    public function addOrigModification($key, $content)
    {
        $this->origModification[$key] = $content;
        return $this;
    }

    /**
     * @param $message
     */
    public function addLog($message)
    {
        $this->logger->write($message);
    }

    public function updateMaintenance($maintenance)
    {
        $this->model_setting_setting->editSettingValue(
            'config',
            'config_maintenance',
            $maintenance
        );
    }
}