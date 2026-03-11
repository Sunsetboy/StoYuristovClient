<?php
declare(strict_types=1);

namespace StoYuristov\Tests;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use StoYuristov\Exception\ApiException;
use StoYuristov\Exception\ValidationException;
use StoYuristov\LeadResponse;
use StoYuristov\StoYuristovClient;
use StoYuristov\StoYuristovLead;

class StoYuristovClientTest extends TestCase
{
    private MockClient $httpClient;
    private StoYuristovClient $sdk;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory    = new Psr17Factory();
        $this->httpClient = new MockClient();

        $this->sdk = new StoYuristovClient(
            appId: 42,
            secretKey: 'secret',
            httpClient: $this->httpClient,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
        );
    }

    // --- Happy path ---

    public function test_sendLead_returns_LeadResponse_on_success(): void
    {
        $this->httpClient->addResponse(new Response(
            status: 200,
            body: json_encode(['code' => 0, 'message' => 'OK', 'leadId' => 99]),
        ));

        $response = $this->sdk->sendLead($this->validLead());

        $this->assertInstanceOf(LeadResponse::class, $response);
        $this->assertSame(99, $response->leadId);
        $this->assertSame('OK', $response->message);
        $this->assertSame(0, $response->code);
    }

    public function test_sendLead_returns_LeadResponse_without_leadId(): void
    {
        $this->httpClient->addResponse(new Response(
            status: 200,
            body: json_encode(['code' => 0, 'message' => 'OK']),
        ));

        $response = $this->sdk->sendLead($this->validLead());

        $this->assertNull($response->leadId);
    }

    // --- Validation ---

    public function test_sendLead_throws_ValidationException_when_name_is_empty(): void
    {
        $this->expectException(ValidationException::class);

        $lead = new StoYuristovLead('', '+79001234567', 'Москва',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос');

        $this->sdk->sendLead($lead);
    }

    public function test_sendLead_throws_ValidationException_when_phone_is_empty(): void
    {
        $this->expectException(ValidationException::class);

        $lead = new StoYuristovLead('Иван', '', 'Москва',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос');

        $this->sdk->sendLead($lead);
    }

    public function test_sendLead_throws_ValidationException_when_question_is_empty(): void
    {
        $this->expectException(ValidationException::class);

        $lead = new StoYuristovLead('Иван', '+79001234567', 'Москва',
            StoYuristovLead::TYPE_QUESTION, '');

        $this->sdk->sendLead($lead);
    }

    public function test_sendLead_throws_ValidationException_when_town_is_empty(): void
    {
        $this->expectException(ValidationException::class);

        $lead = new StoYuristovLead('Иван', '+79001234567', '',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос');

        $this->sdk->sendLead($lead);
    }

    public function test_ValidationException_contains_all_error_messages(): void
    {
        $lead = new StoYuristovLead('', '', '',
            StoYuristovLead::TYPE_QUESTION, '');

        try {
            $this->sdk->sendLead($lead);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(4, $errors); // name, phone, question, town
            $this->assertStringContainsString('имя', $errors[0]);
            $this->assertStringContainsString('телефон', $errors[1]);
            $this->assertStringContainsString('вопрос', $errors[2]);
            $this->assertStringContainsString('город', $errors[3]);
        }
    }

    public function test_ValidationException_does_not_send_http_request(): void
    {
        $lead = new StoYuristovLead('', '', '', StoYuristovLead::TYPE_QUESTION, '');

        try {
            $this->sdk->sendLead($lead);
        } catch (ValidationException) {
        }

        $this->assertCount(0, $this->httpClient->getRequests());
    }

    // --- API errors ---

    public function test_sendLead_throws_ApiException_on_http_500(): void
    {
        $this->httpClient->addResponse(new Response(500, body: '{}'));

        $this->expectException(ApiException::class);
        $this->sdk->sendLead($this->validLead());
    }

    public function test_sendLead_throws_ApiException_on_http_404(): void
    {
        $this->httpClient->addResponse(new Response(404, body: '{}'));

        $this->expectException(ApiException::class);
        $this->sdk->sendLead($this->validLead());
    }

    public function test_sendLead_throws_ApiException_when_api_returns_error_code(): void
    {
        $this->httpClient->addResponse(new Response(
            status: 400,
            body: json_encode(['message' => 'Invalid signature']),
        ));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid signature');
        $this->sdk->sendLead($this->validLead());
    }

    public function test_sendLead_throws_ApiException_on_empty_response_body(): void
    {
        $this->httpClient->addResponse(new Response(200, body: ''));

        $this->expectException(ApiException::class);
        $this->sdk->sendLead($this->validLead());
    }

    public function test_sendLead_throws_ApiException_on_non_json_response_body(): void
    {
        $this->httpClient->addResponse(new Response(200, body: 'Internal Server Error'));

        $this->expectException(ApiException::class);
        $this->sdk->sendLead($this->validLead());
    }

    public function test_sendLead_throws_ApiException_on_truncated_json_response_body(): void
    {
        $this->httpClient->addResponse(new Response(200, body: '{"code": 0, "mess'));

        $this->expectException(ApiException::class);
        $this->sdk->sendLead($this->validLead());
    }

    public function test_ApiException_exposes_http_status_code(): void
    {
        $this->httpClient->addResponse(new Response(
            status: 422,
            body: json_encode(['code' => 1, 'message' => 'Unprocessable']),
        ));

        try {
            $this->sdk->sendLead($this->validLead());
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->getHttpStatusCode());
        }
    }

    // --- Request inspection ---

    public function test_sendLead_sends_correct_appId_in_body(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertSame(42, $body['appId']);
    }

    public function test_sendLead_sends_testMode_0_by_default(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertSame(0, $body['testMode']);
    }

    public function test_sendLead_includes_signature_in_body(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertArrayHasKey('signature', $body);
        $this->assertNotEmpty($body['signature']);
    }

    public function test_sendLead_sends_lead_fields_in_body(): void
    {
        $this->queueSuccessResponse();

        $lead = new StoYuristovLead(
            name: 'Иван',
            phone: '+79001234567',
            town: 'Москва',
            type: StoYuristovLead::TYPE_QUESTION,
            question: 'Как расторгнуть договор?',
            email: 'ivan@example.com',
        );
        $this->sdk->sendLead($lead);

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertSame('Иван', $body['name']);
        $this->assertSame('+79001234567', $body['phone']);
        $this->assertSame('ivan@example.com', $body['email']);
        $this->assertSame('Москва', $body['town']);
        $this->assertSame('Как расторгнуть договор?', $body['question']);
        $this->assertSame(StoYuristovLead::TYPE_QUESTION, $body['type']);
    }

    public function test_sendLead_sends_price_when_set(): void
    {
        $this->queueSuccessResponse();

        $lead = new StoYuristovLead('Иван', '+79001234567', 'Москва',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос', email: 'a@b.com', price: 35);
        $this->sdk->sendLead($lead);

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertSame(35, $body['price']);
    }

    public function test_sendLead_omits_price_when_not_set(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertArrayNotHasKey('price', $body);
    }

    public function test_sendLead_sends_widgetUuid_when_set(): void
    {
        $this->queueSuccessResponse();

        $lead = new StoYuristovLead('Иван', '+79001234567', 'Москва',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос', email: 'a@b.com', widgetUuid: 'abc-123');
        $this->sdk->sendLead($lead);

        $body = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertSame('abc-123', $body['widgetUuid']);
    }

    public function test_sendLead_posts_to_sendLead_endpoint(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $uri = (string) $this->httpClient->getLastRequest()->getUri();

        $this->assertStringEndsWith('lead/create/', $uri);
    }

    public function test_sendLead_uses_correct_content_type(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead($this->validLead());

        $contentType = $this->httpClient->getLastRequest()->getHeaderLine('Content-Type');

        $this->assertSame('application/json', $contentType);
    }

    // --- Signature ---

    public function test_signature_changes_when_lead_data_changes(): void
    {
        $this->queueSuccessResponse();
        $this->sdk->sendLead(new StoYuristovLead('Иван', '+79001234567', 'Москва',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос один', email: 'a@b.com'));

        $body1 = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->queueSuccessResponse();
        $this->sdk->sendLead(new StoYuristovLead('Пётр', '+79009876543', 'Казань',
            StoYuristovLead::TYPE_QUESTION, 'Вопрос два', email: 'b@c.com'));

        $body2 = json_decode((string) $this->httpClient->getLastRequest()->getBody(), true);

        $this->assertNotSame($body1['signature'], $body2['signature']);
    }

    // --- Test mode ---

    public function test_testMode_flag_is_sent_as_1(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(200, body: json_encode(['code' => 0, 'message' => 'OK'])));

        $sdk = new StoYuristovClient(42, 'secret', $mockClient, $this->factory, $this->factory, testMode: true);
        $sdk->sendLead($this->validLead());

        $body = json_decode((string) $mockClient->getLastRequest()->getBody(), true);

        $this->assertSame(1, $body['testMode']);
    }

    // --- Helpers ---

    private function validLead(): StoYuristovLead
    {
        return new StoYuristovLead(
            name: 'Иван',
            phone: '+79001234567',
            email: 'ivan@example.com',
            town: 'Москва',
            type: StoYuristovLead::TYPE_QUESTION,
            question: 'Как расторгнуть договор?',
        );
    }

    private function queueSuccessResponse(): void
    {
        $this->httpClient->addResponse(new Response(
            status: 200,
            body: json_encode(['code' => 0, 'message' => 'OK', 'leadId' => 1]),
        ));
    }
}
