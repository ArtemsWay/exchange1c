<?php

namespace ArtemsWay\Exchange1C;

use ArtemsWay\Parser1C\Parsers\ParserInterface;
use ArtemsWay\Parser1C\Parsers\DOM\OrderParser;
use ArtemsWay\Parser1C\Parsers\DOM\OffersParser;
use ArtemsWay\Parser1C\Parsers\DOM\ImportParser;

class Exchange1C
{
    use ModeTrait;
    use PrintTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var array
     */
    protected $parsers = [];

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @var array
     */
    protected $configs = [
        'ordersTemplate' => __DIR__ . '/templates/orders.php'
    ];

    /**
     * @var string
     */
    protected $postSource = 'php://input';

    public function __construct($configs)
    {
        $this->setConfigs($configs);

        $this->parseRequestQuery();

        $this->setDefaultParsers();
    }

    /**
     * @param array $configs
     */
    public function setConfigs($configs)
    {
        $this->configs = array_merge($this->configs, $configs);
    }

    protected function parseRequestQuery()
    {
        $this->type = isset($_GET['type']) ? $_GET['type'] : null;
        $this->mode = isset($_GET['mode']) ? $_GET['mode'] : null;
        $this->filename = isset($_GET['filename']) ? $_GET['filename'] : null;

        if (is_null($this->type) || is_null($this->mode)) {
            throw new \Exception("type или mode не установлены.");
        }
    }

    protected function setDefaultParsers()
    {
        $this->setFileParser('import', new ImportParser);
        $this->setFileParser('offers', new OffersParser);
        $this->setFileParser('orders', new OrderParser);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function execute()
    {
        $method = "mode{$this->mode}";

        if (!method_exists($this, $method)) {
            $method = 'modeDefault';
        }

        if ($this->mode !== 'checkauth' && !$this->access()) {
            return $this->printFailure("Отказано в доступе.");
        }

        return call_user_func([$this, $method]);
    }

    /**
     * @return boolean
     */
    protected function access()
    {
        return $this->triggerEventCallback('access');
    }

    /**
     * @param string $type
     * @param ParserInterface $parser
     */
    public function setFileParser($type, ParserInterface $parser)
    {
        $this->parsers[$type] = $parser;
    }

    /**
     * @param string $event
     * @param callable $callback
     */
    public function on($event, callable $callback)
    {
        $this->callbacks[$event] = $callback;
    }

    /**
     * @param null|string $event
     * @param array $args
     * @return mixed
     */
    protected function triggerEventCallback($event = null, $args = [])
    {
        $callback = $this->getEventCallback($event);

        return call_user_func_array($callback, $args);
    }

    /**
     * @param string $source
     */
    public function setPostSource($source)
    {
        $this->postSource = $source;
    }

    /**
     * @return string
     */
    public function getPostSource()
    {
        return $this->postSource;
    }

    /**
     * @param string $event
     * @return callable
     * @throws \Exception
     */
    protected function getEventCallback($event)
    {
        $type = $this->type;
        $event = $event ?: $this->mode;

        $fullEvent = "{$type}:{$event}";

        if (isset($this->callbacks[$fullEvent])) {
            return $this->callbacks[$fullEvent];
        }

        if (isset($this->callbacks[$event])) {
            return $this->callbacks[$event];
        }

        throw new \Exception("Не установлена callback функция для события: $event");
    }

    /**
     * @return string
     */
    protected function getFile()
    {
        return $this->getDir() . '/' . $this->filename;
    }

    /**
     * @return string
     */
    protected function getDir()
    {
        return $this->getConfig('dir') . '/' . $this->type;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    protected function getConfig($key)
    {
        if (!isset($this->configs[$key])) {
            throw new \Exception("Не установлено значение для ключа: $key");
        }

        return $this->configs[$key];
    }

    /**
     * @param string $type
     * @return ParserInterface
     * @throws \Exception
     */
    protected function getParser($type)
    {
        if (!isset($this->parsers[$type])) {
            throw new \Exception("Не установлен парсер для файла типа: $type");
        }

        return $this->parsers[$type];
    }

    /**
     * @param string $file
     * @return string
     * @throws \Exception
     */
    protected function getFileType($file)
    {
        $basename = basename($file);

        if (!preg_match('/^[a-zA-Z]+/', $basename, $matches)) {
            throw new \Exception("Не удалось определить тип файла: $file");
        }

        return array_shift($matches);
    }

    /**
     * @param array $orders
     * @return string
     */
    protected function getOrdersXml($orders)
    {
        ob_start();

        require $this->getOrdersTemplate();

        return ob_get_clean();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getOrdersTemplate()
    {
        $template = $this->getConfig('ordersTemplate');

        if (!file_exists($template)) {
            throw new \Exception("Не верно указан путь к шаблону заказов: $template");
        }

        return $template;
    }

    /**
     * @param string $dir
     */
    protected function prepareDirectory($dir)
    {
        if (is_dir($dir)) {
            $this->clearDirectory($dir);
        }

        mkdir($dir, 0755, true);
    }

    /**
     * @param string $path
     */
    protected function clearDirectory($path)
    {
        if (is_dir($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $this->clearDirectory($path . '/' . $file);
                }
            }

            rmdir($path);
        } else {
            unlink($path);
        }
    }

    protected function unzip($dir, $filePath)
    {
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive;
            $zipError = false;

            if ($zip->open($filePath, \ZipArchive::CHECKCONS) === true) {
                if (!($zip->extractTo($dir))) {
                    return false;
                }

                $zip->close();
            } else {
                $zipError = true;
            }
        } else {
            $zipError = true;
        }

        if ($zipError) {
            exec("unzip -oqq $filePath -d $dir");
        }

        return true;
    }
}
