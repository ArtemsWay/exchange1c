<?php

namespace ArtemsWay\Exchange1C\Tests;

use PHPUnit\Framework\TestCase;
use ArtemsWay\Exchange1C\Exchange1C;

class Exchange1CTest extends TestCase
{
    public function testModeCheckauth()
    {
        $_GET['type'] = 'catalog';
        $_GET['mode'] = 'checkauth';
        $_SERVER['PHP_AUTH_USER'] = 'session_name';
        $_SERVER['PHP_AUTH_PW'] = 'session_id';

        $exchange = new Exchange1C([]);

        $exchange->on('checkauth', function ($login, $password) {
            $name = $login;
            $id = $password;

            return compact('name', 'id');
        });

        $this->assertEquals("success\nsession_name\nsession_id", $exchange->execute());
    }

    public function testModeInit()
    {
        $_GET['type'] = 'catalog';
        $_GET['mode'] = 'init';

        $exchange = new Exchange1C([
            'dir' => __DIR__ . '/../tmp'
        ]);

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('init', function () {
            return ['yes', 1024];
        });

        $this->assertEquals("zip=yes\nfile_limit=1024", $exchange->execute());
    }

    public function testSaleModeInit()
    {
        $_GET['type'] = 'sale';
        $_GET['mode'] = 'init';

        $exchange = new Exchange1C([
            'dir' => __DIR__ . '/../tmp'
        ]);

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('init', function () {
            return ['yes', 1024];
        });

        $this->assertEquals("zip=yes\nfile_limit=1024", $exchange->execute());
    }

    /**
     * @depends testModeInit
     */
    public function testModeFile()
    {
        $_GET['type'] = 'catalog';
        $_GET['mode'] = 'file';
        $_GET['filename'] = 'import0_1.xml';

        $exchange = new Exchange1C([
            'dir' => __DIR__ . '/../tmp'
        ]);

        $exchange->setPostSource(__DIR__ . '/../fixtures/import0_1.xml');

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('file', function ($filename) {
            return ['success', 'Файл успешно сохранен'];
        });

        $this->assertEquals("success\nФайл успешно сохранен", $exchange->execute());
    }

    /**
     * @depends testSaleModeInit
     */
    public function testSaleModeFile()
    {
        $_GET['type'] = 'sale';
        $_GET['mode'] = 'file';
        $_GET['filename'] = 'orders-cf0b82a8-3e02-43e7-946c-0ff869125614_1.xml';

        $exchange = new Exchange1C([
            'dir' => __DIR__ . '/../tmp'
        ]);

        $exchange->setPostSource(__DIR__ . '/../fixtures/orders-cf0b82a8-3e02-43e7-946c-0ff869125614_1.xml');

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('file', function ($filename) {
            return ['success', 'Файл успешно сохранен'];
        });

        $exchange->on('sale:import', function ($type, $data) {
            return ['success', "Импорт успешно завершён. Тип файла: $type, версия схемы: {$data['schemaVersion']}"];
        });

        $this->assertEquals(
            "success\nИмпорт успешно завершён. Тип файла: orders, версия схемы: 2.07",
            $exchange->execute()
        );
    }

    /**
     * @depends testModeFile
     */
    public function testModeImport()
    {
        $_GET['type'] = 'catalog';
        $_GET['mode'] = 'import';
        $_GET['filename'] = 'import0_1.xml';

        $exchange = new Exchange1C([
            'dir' => __DIR__ . '/../tmp'
        ]);

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('import', function ($type, $data) {
            return ['progress', "Успешно загруженно 20%. Тип файла: $type, версия схемы: {$data['schemaVersion']}"];
        });

        $this->assertEquals(
            "progress\nУспешно загруженно 20%. Тип файла: import, версия схемы: 2.07",
            $exchange->execute()
        );

        $exchange->on('catalog:import', function ($type, $data) {
            return ['success', "Импорт успешно завершён. Тип файла: $type, версия схемы: {$data['schemaVersion']}"];
        });

        $this->assertEquals(
            "success\nИмпорт успешно завершён. Тип файла: import, версия схемы: 2.07",
            $exchange->execute()
        );
    }

    public function testModeQuery()
    {
        $_GET['type'] = 'sale';
        $_GET['mode'] = 'query';

        $exchange = new Exchange1C([]);

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('query', function () {

            $orders = [];

            for ($i = 0; $i < 2; $i++) {
                $order = (object)[
                    'id' => '4bda4442-08dd-49c3-ae90-587e45ca65ce',
                    'number' => 245,
                    'date' => '2017-05-06',
                    'total' => 500,
                    'time' => '12:12:12',
                    'products' => [],
                ];

                $order->products[0] = (object)[
                    'id' => 'dee6e1d0-55bc-11d9-848a-00112f43529a',
                    'name' => 'Кроссовки "ADIDAS"',
                    'price' => 250,
                    'count' => 2,
                    'total' => 500
                ];

                $orders[] = $order;
            }

            return $orders;
        });

        $this->assertRegExp('/dee6e1d0-55bc-11d9-848a-00112f43529a/', $exchange->execute());
    }

    public function testModeSuccess()
    {
        $_GET['type'] = 'sale';
        $_GET['mode'] = 'success';

        $exchange = new Exchange1C([]);

        $exchange->on('access', function () {
            return true;
        });

        $exchange->on('success', function () {
            return ['success', 'Все заказы успешно обработаны'];
        });

        $this->assertEquals("success\nВсе заказы успешно обработаны", $exchange->execute());
    }
}
