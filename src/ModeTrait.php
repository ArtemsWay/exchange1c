<?php

namespace ArtemsWay\Exchange1C;

use ArtemsWay\Parser1C\Parser1C;

trait ModeTrait
{
    /**
     * @return string
     */
    public function modeCheckauth()
    {
        $login = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        if (!$login || !$password) {
            return $this->printFailure("Ошибка HTTP аутентификации user или password не установлены.");
        }

        if (!$response = $this->triggerEventCallback(null, compact('login', 'password'))) {
            return $this->printFailure("Доступ запрещён.");
        }

        return $this->printSuccess($response['name'] . "\n" . $response['id']);
    }

    /**
     * @return string
     */
    public function modeInit()
    {
        $dir = $this->getDir();

        $this->prepareDirectory($dir);

        $response = $this->triggerEventCallback();

        return $this->printZip($response);
    }

    /**
     * @return string
     */
    public function modeFile()
    {
        $dir = $this->getDir();

        $filePath = $dir . '/' . $this->filename;

        $file = fopen($filePath, 'ab');

        $data = file_get_contents($this->getPostSource());

        $result = fwrite($file, $data);

        $size = strlen($data);

        if ($result !== $size) {
            return $this->printFailure("Ошибка записи файла: $filePath");
        }

        if (substr($filePath, -3) == 'zip') {
            if (!$this->unzip($dir, $filePath)) {
                return $this->printFailure("Не удалось распаковать архив: $filePath");
            }
        }

        $response = $this->triggerEventCallback(null, [$this->filename]);

        // Так как 1С не делает запрос вида type=sale&mode=import
        // Мы просто его эмулируем
        if ($this->type == 'sale') {
            $this->mode = 'import';

            return $this->modeImport();
        }

        return $this->printStatus($response);
    }

    /**
     * @return string
     */
    public function modeImport()
    {
        $file = $this->getFile();

        $type = $this->getFileType($file);

        $parser = $this->getParser($type);

        $parser = new Parser1C($file, $parser);

        $data = $parser->load()->parseAll()->getData();

        $response = $this->triggerEventCallback(null, compact('type', 'data'));

        return $this->printStatus($response);
    }

    /**
     * @return string
     */
    public function modeQuery()
    {
        $orders = $this->triggerEventCallback();

        $xml = $this->getOrdersXml($orders);

        return $this->printOrders($xml);
    }

    /**
     * @return string
     */
    public function modeSuccess()
    {
        $response = $this->triggerEventCallback();

        return $this->printStatus($response);
    }

    /**
     * @return string
     */
    public function modeDefault()
    {
        $response = $this->triggerEventCallback();

        return $this->printStatus($response);
    }
}
