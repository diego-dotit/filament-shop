<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Centralises JSON response formatting for API endpoints.
 *
 * Success format:    { success: true,  data: {...},  message: null|"..." }
 * Error format:      { success: false, data: null,   error: "...",  message: "..." }
 * Validation format: { success: false, data: null,   error: "validation_failed",
 *                       message: "...", errors: {field: ["message"]} }
 */
class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed        $data    The primary response payload (resource, array, scalar, or null).
     * @param  int          $status  HTTP status code (default 200).
     * @param  string|null  $message Optional human-readable message.
     * @param  array        $extra   Additional top-level fields merged into the response envelope
     *                               (e.g. ['token' => '…'] for the login endpoint).
     */
    public static function success(
        mixed $data = null,
        int $status = 200,
        ?string $message = null,
        array $extra = [],
    ): JsonResponse {
        return response()->json(
            array_merge(
                [
                    'success' => true,
                    'data'    => $data,
                    'message' => $message,
                ],
                $extra,
            ),
            $status,
        );
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $error    Machine-readable error code (e.g. 'not_found').
     * @param  string  $message  Human-readable error description.
     * @param  int     $status   HTTP status code (default 400).
     * @param  mixed   $data     Optional additional data (rarely used for errors).
     */
    public static function error(
        string $error,
        string $message,
        int $status = 400,
        mixed $data = null,
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'data'    => $data,
                'error'   => $error,
                'message' => $message,
            ],
            $status,
        );
    }

    /**
     * Return a 422 Unprocessable Entity response for validation failures.
     *
     * @param  string  $message  Human-readable summary (e.g. 'The given data was invalid.').
     * @param  array   $errors   Field-keyed validation error messages
     *                           (e.g. ['email' => ['The email field is required.']]).
     */
    public static function validationError(string $message, array $errors): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'data'    => null,
                'error'   => 'validation_failed',
                'message' => $message,
                'errors'  => $errors,
            ],
            422,
        );
    }
}
