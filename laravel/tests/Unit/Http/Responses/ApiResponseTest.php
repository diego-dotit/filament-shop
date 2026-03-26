<?php

namespace Tests\Unit\Http\Responses;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    // -----------------------------------------------------------------------
    // success()
    // -----------------------------------------------------------------------

    public function test_success_returns_json_response_with_correct_structure(): void
    {
        $response = ApiResponse::success(['id' => 1, 'name' => 'Widget']);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1, 'name' => 'Widget'], $data['data']);
        $this->assertNull($data['message']);
    }

    public function test_success_defaults_to_200_status_code(): void
    {
        $response = ApiResponse::success(['foo' => 'bar']);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_success_accepts_custom_status_code(): void
    {
        $response = ApiResponse::success(['created' => true], 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_success_accepts_a_message(): void
    {
        $response = ApiResponse::success(null, 200, 'Operation completed.');

        $data = $response->getData(true);
        $this->assertEquals('Operation completed.', $data['message']);
    }

    public function test_success_accepts_extra_top_level_fields(): void
    {
        $response = ApiResponse::success(['id' => 1], 200, null, ['token' => 'abc123']);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals('abc123', $data['token']);
    }

    public function test_success_data_can_be_null(): void
    {
        $response = ApiResponse::success(null);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertNull($data['data']);
    }

    // -----------------------------------------------------------------------
    // error()
    // -----------------------------------------------------------------------

    public function test_error_returns_json_response_with_correct_structure(): void
    {
        $response = ApiResponse::error('not_found', 'Resource not found.', 404);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertNull($data['data']);
        $this->assertEquals('not_found', $data['error']);
        $this->assertEquals('Resource not found.', $data['message']);
    }

    public function test_error_uses_supplied_http_status_code(): void
    {
        $response = ApiResponse::error('unauthorized', 'Unauthenticated.', 401);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_error_defaults_to_400_status_code(): void
    {
        $response = ApiResponse::error('bad_request', 'Bad request.');

        $this->assertSame(400, $response->getStatusCode());
    }

    // -----------------------------------------------------------------------
    // validationError()
    // -----------------------------------------------------------------------

    public function test_validation_error_returns_422_with_correct_structure(): void
    {
        $errors   = ['email' => ['The email field is required.']];
        $response = ApiResponse::validationError('The given data was invalid.', $errors);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertNull($data['data']);
        $this->assertEquals('validation_failed', $data['error']);
        $this->assertEquals('The given data was invalid.', $data['message']);
        $this->assertEquals($errors, $data['errors']);
    }

    public function test_validation_error_includes_all_field_errors(): void
    {
        $errors = [
            'name'  => ['The name field is required.'],
            'email' => ['The email field is required.', 'The email must be a valid email address.'],
        ];

        $response = ApiResponse::validationError('Validation failed.', $errors);

        $data = $response->getData(true);
        $this->assertArrayHasKey('name', $data['errors']);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertCount(2, $data['errors']['email']);
    }
}
