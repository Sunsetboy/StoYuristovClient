# Клиент для работы с API сервиса 100 Юристов

## Установка

```bash
composer require 100yuristov/sto-yuristov-client
```

Пакет не привязан к конкретной HTTP-библиотеке. Вам нужно установить любой PSR-18-совместимый HTTP-клиент. Например, Guzzle:

```bash
composer require guzzlehttp/guzzle php-http/guzzle7-adapter
```

Или Symfony HttpClient:

```bash
composer require symfony/http-client nyholm/psr7
```

---

## Базовое использование

### С Guzzle

```php
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use GuzzleHttp\Psr7\HttpFactory;
use StoYuristov\StoYuristovClient;
use StoYuristov\StoYuristovLead;

$adapter = GuzzleAdapter::createWithConfig(['timeout' => 5]);
$factory = new HttpFactory();

$client = new StoYuristovClient(
    appId: ВАШ_APP_ID,
    secretKey: 'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ',
    httpClient: $adapter,
    requestFactory: $factory,
    streamFactory: $factory,
);
```

### С Symfony HttpClient

```php
use Symfony\Component\HttpClient\Psr18Client;
use StoYuristov\StoYuristovClient;

$psr18 = new Psr18Client();

$client = new StoYuristovClient(
    appId: ВАШ_APP_ID,
    secretKey: 'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ',
    httpClient: $psr18,
    requestFactory: $psr18,
    streamFactory: $psr18,
);
```

---

## Отправка лида

```php
use StoYuristov\StoYuristovLead;
use StoYuristov\Exception\ValidationException;
use StoYuristov\Exception\ApiException;

$lead = new StoYuristovLead(
    name: 'Иван Иванов',
    phone: '+79001234567',
    email: 'ivan@example.com',
    town: 'Москва',
    type: StoYuristovLead::TYPE_QUESTION,
    question: 'Как составить договор аренды?',
    price: 35, // опционально, если включён приём цены лида
);

try {
    $response = $client->sendLead($lead);
    echo $response->message; // описание ответа
    echo $response->leadId;  // ID созданного лида
} catch (ValidationException $e) {
    // Ошибки валидации на стороне клиента
    print_r($e->getErrors());
} catch (ApiException $e) {
    // Ошибка API или HTTP
    echo $e->getMessage();
    echo $e->getHttpStatusCode();
} catch (\Psr\Http\Client\ClientExceptionInterface $e) {
    // Ошибка транспорта (сеть недоступна и т.п.)
    echo $e->getMessage();
}
```

### Типы лида

| Константа | Значение | Описание |
|---|---|---|
| `StoYuristovLead::TYPE_QUESTION` | `1` | Вопрос (по умолчанию) |
| `StoYuristovLead::TYPE_CALL` | `2` | Запрос обратного звонка |

---

## Тестовый режим

В тестовом режиме лиды проверяются, но не сохраняются.

```php
$client = new StoYuristovClient(
    appId: ВАШ_APP_ID,
    secretKey: 'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ',
    httpClient: $httpClient,
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    testMode: true,
);
```

Для тестирования против локального окружения можно передать кастомный `baseUrl`:

```php
$client = new StoYuristovClient(
    appId: ВАШ_APP_ID,
    secretKey: 'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ',
    httpClient: $httpClient,
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    baseUrl: 'http://100yuristov.local/api/',
    testMode: true,
);
```

---

## Повторные попытки и таймауты

Библиотека не навязывает конкретный механизм ретраев — это задача HTTP-клиента. Пример с `php-http/retry-plugin`:

```bash
composer require php-http/httplug php-http/retry-plugin php-http/guzzle7-adapter
```

```php
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;

$guzzle  = new GuzzleClient(['timeout' => 5, 'connect_timeout' => 2]);
$adapter = new GuzzleAdapter($guzzle);
$factory = new HttpFactory();

$retryPlugin  = new RetryPlugin(['retries' => 3]);
$pluginClient = new PluginClient($adapter, [$retryPlugin]);

$client = new StoYuristovClient(
    appId: ВАШ_APP_ID,
    secretKey: 'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ',
    httpClient: $pluginClient,
    requestFactory: $factory,
    streamFactory: $factory,
);
```

---

## Подпись запроса

Подпись вычисляется автоматически перед каждой отправкой, указывать её вручную не нужно.

## Запуск тестов локально

```bash
composer install
make test
```