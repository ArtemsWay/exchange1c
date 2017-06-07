<?php

namespace ArtemsWay\Exchange1C;

trait PrintTrait
{
    /**
     * @param array $response
     * @return string
     * @throws \Exception
     */
    protected function printStatus($response)
    {
        list($status, $message) = $response;

        switch ($status) {
            case 'success':
                return $this->printSuccess($message);
                break;
            case 'progress':
                return $this->printProgress($message);
                break;
            case 'failure':
                return $this->printFailure($message);
                break;
            default:
                throw new \Exception("Неизвестный тип статуса: $status");
        }
    }

    /**
     * @param array $response
     * @return string
     */
    protected function printZip($response)
    {
        list($zip, $size) = $response;

        return "zip={$zip}" . "\n" . "file_limit={$size}";
    }

    /**
     * @param string $message
     * @return string
     */
    protected function printSuccess($message = '')
    {
        return "success\n" . $message;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function printProgress($message = '')
    {
        return "progress\n" . $message;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function printFailure($message = '')
    {
        return "failure\n" . $message;
    }

    /**
     * @param string $xml
     * @return string
     */
    protected function printOrders($xml = '')
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml');
        }

        return iconv('utf-8', 'windows-1251//IGNORE', $xml);
    }
}
