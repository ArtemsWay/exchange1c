# Библиотека для обмена 1С с сайтом

### Установка

1. Обновите ваш composer.json файл.

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/ArtemsWay/exchange1c"
    }
],
"require": {

    "artemsway/exchange1c": "dev-master"
},
```

2. Выполните команду ``` composer update ```.

### Базовое использование

```php
    require 'vendor/autoload.php';
    
    use ArtemsWay\Exchange1C\Exchange1C;
    
    $exchange = new Exchange1C([
        'dir' => 'path/to/exchange/dir', // Папка, в которую будут загружаться файлы обмена
        'ordersTemplate' => 'path/to/orders_template.php' // Шаблон файла заказов
    ]);
```

После чего нужно зарегистрировать ряд callback функций.

Таблица доступных событий

| Для type=*   | Для type=catalog  | Для type=sale  |
| --------- | ----------------- | -------------- |
| checkauth | catalog:checkauth | sale:checkauth |
| init | catalog:init | sale:init |
| file | catalog:file | sale:file |
| import | catalog:import | sale:import |
| query | -- | sale:query |
| success | -- | sale:success |
    
```php
    /**
     * Регистрация callback функции для любого типа ($_GET['type']).
     * В качестве аргументов принимает логин и пароль полученные из HTTP-аутентификации.
     *
     * Вернуть функция должна name и id сессии либо null если логин или пароль не подходит
     */
    $exchange->on('checkauth', function ($login, $password) {
        return [
            'name' => 'session_name',
            'id' => 'session_id'
        ];
    });
    
    /**
     * В данной функции нужно выполнить проверку доступа
     * Вернуть функция должна boolean:
     * true - пользователь имеет доступ
     * false - доступ закрыт
     */
    $exchange->on('access', function () {
        return true;
    });
    
    /**
     * Функция инициализации обмена.
     *
     * Должна вернуть массив с двумя элементами:
     * zip - будут ли данные передаватся в виде архива, доступные значения ('yes', 'no'),
     * filesize - максимальный размер принимаемого файла в байтах (1024 = 1КБ), в php по умолчанию upload_max_filesize=2M
     */
    $exchange->on('init', function () {
        return ['yes', 1024*1024*2];
    });
    
    /**
     * Вызывается после загрузки файла.
     * В качестве аргумента принимает имя загруженного файла.
     *
     * Должна вернуть массив с двумя элементами:
     * status - статус операции, доступные значения ('success', 'failure'),
     * massage - текст сообщения который будет оправлен в 1С
     */
    $exchange->on('file', function ($filename) {
        return ['success', 'Файл успешно сохранен'];
    });
    
    /**
     * Вызывается после парсинга xml файла.
     * В качестве аргументов принимает тип файла и массив данных полученных при парсинге xml файла.
     * Должна вернуть массив с двумя элементами:
     * status - статус операции, доступные значения ('success', 'progress', 'failure'),
     * massage - текст сообщения который будет оправлен в 1С
     */
    $exchange->on('import', function ($type, $data) {
        return ['success', 'Импорт успешно завершён.';
        //return ['progress', 'Успешно загруженно 20%.'; // В случае статуса 'progress', 1С выполнит тот же запрос пока система не вернет статус 'success'.
    });
    
    /**
     * Должна вернуть массив заказов, который в следствии будет передан в файл ordersTemplate.
     */
    $exchange->on('query', function () {
        $orders = 'sql запрос для получения заказов'
    
        return $orders;
    });
    
    /**
     * Вызывается после успешной обработки заказов на стороне 1С.
     *
     * Должна вернуть массив с двумя элементами:
     * status - статус операции, доступные значения ('success', 'failure'),
     * massage - текст сообщения который будет оправлен в 1С
     */
    $exchange->on('success', function () {
        $orders = 'sql запрос для получения заказов'
    
        return $orders;
    });
```

После чего выполняем обмен

```php

    try {
    
        $response = $exchange->execute();
        
    } catch (\Exception $e) {
    
        // Упс произошла ошибка детали в $e
        
        $response = 'failure';
    }

    // И выводим ответ
    echo $response;
```

### Дополнение

В качестве парсера используется библиотека [parser1c](https://github.com/ArtemsWay/parser1c)

Есть возможность добавлять новые классы парсеров

```php
    $exchange->setFileParser('partners', new PartnersParser);
    
    // И регистрация на новые события
    $exchange->on('partners:init', function() {});
    
    // Либо же можно использовать общее событие для всех типов
    $exchange->on('init' , function() {});
```