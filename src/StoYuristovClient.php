<?php

namespace StoYuristov;
/**
 * Клиент для работы с API сервиса 100 Юристов
 * @author Michael Krutikov <misha.sunsetboy@gmail.com>
 */
class StoYuristovClient
{

    protected $appId; // идентификатор кампании партнера
    protected $secretKey; // секретный ключ кампании
    protected $curlLink; // линк Curl
    protected $signature; // подпись запроса
    protected $apiUrlTest = 'http://100yuristov/api/sendLead/';
    protected $apiUrl = 'https://100yuristov.com/api/sendLead/';
    protected $testMode; // 0|1 Включение / выключение тестового режима
    // параметры лида
    public $name;
    public $phone;
    public $question;
    public $town;
    public $email;
    public $type;
    public $widgetUuid;
    public $price;

    /**
     * Конструктор
     *
     * @param integer $appId
     * @param string $secretKey
     */
    public function __construct($appId, $secretKey, $testMode = 0, $apiUrl = 'https://100yuristov.com/api/sendLead/')
    {
        $this->appId = $appId;
        $this->secretKey = $secretKey;
        $this->testMode = $testMode;

        if ($apiUrl !== null) {
            $this->apiUrl = $apiUrl;
            $this->apiUrlTest = $apiUrl;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (1 == $this->testMode) ? $this->apiUrlTest : $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

        $this->curlLink = $ch;
    }

    /**
     * Вычисление сигнатуры
     */
    protected function calculateSignature()
    {
        $this->signature = md5($this->name . $this->phone . $this->town . $this->question . $this->appId . $this->secretKey);
    }

    /**
     * Проверка данных перед отправкой в API на стороне клиента
     * @return mixed Результат проверки. true если все в порядке, иначе массив ошибок
     */
    protected function validate()
    {
        $errors = [];

        if (!$this->name) {
            $errors[] = 'Не указано имя';
        }
        if (!$this->phone) {
            $errors[] = 'Не указан номер телефона';
        }
        if (!$this->question) {
            $errors[] = 'Не указан текст вопроса';
        }
        if (!$this->town) {
            $errors[] = 'Не указан город';
        }

        if (empty($errors)) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * Возвращает массив параметров для POST запроса
     *
     * @return array Массив параметров
     */
    protected function getParams()
    {
        return [

            'appId' => $this->appId,
            'signature' => $this->signature,
            'testMode' => $this->testMode,
            'widgetUuid' => $this->widgetUuid,
        ];
    }

    /**
     * Отправляет лид в api
     */
    public function sendLead(StoYuristovLead $lead): LeadResponse
    {
        // проверяем данные
        $errors = $lead->validate();
        if ($errors !== true) {
            return ['message' => 'Некорректные данные', 'errors' => $errors];
        }

        // вычисляем сигнатуру
        $this->calculateSignature();

        // Создаем запрос с POST параметрами
        curl_setopt($this->curlLink, CURLOPT_POSTFIELDS, $this->getParams());
        $jsonResponse = curl_exec($this->curlLink);
        $curlInfo = curl_getinfo($this->curlLink);
        curl_close($this->curlLink);

        if ($jsonResponse !== false) {
            // Возвращаем ответ от API в виде ассоциативного массива (code => код_ответа, message => текст ответа)
            return json_decode($jsonResponse, true);
        } else {
            return ['message' => 'Ошибка при отправке лида на сервер'];
        }
    }

}
