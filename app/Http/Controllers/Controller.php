<?php
namespace App\Http\Controllers;

use App\Models\ErrorLog;
use OpenApi\Annotations as OA;

abstract class Controller
{
    public function exceptionError($e, $exception, $status = 400)
    {
        $request = request();

        ErrorLog::create([
            'user_id' => optional($request->user())->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'payload' => json_encode($request->all()),
        ]);


        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => 'Exception Error : ' . $exception,
        ], $status);
    }


    public function successResponse($data, $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function errorResponse($message, $status = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null
        ], $status);
    }

    /**
     * Universal pagination response handler
     * Supports both Laravel Paginator objects and manual array pagination
     *
     * @param mixed $data Laravel Paginator object OR array of items
     * @param string $message Response message
     * @param int $status HTTP status code
     * @param array|null $additionalData Extra data to merge (e.g., summary, statistics)
     * @param array|null $manualPagination Manual pagination params when $data is array: ['total' => int, 'per_page' => int, 'current_page' => int, 'next_url' => string|null, 'prev_url' => string|null]
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginationResponse($data, $message = 'Success', $status = 200, $additionalData = null, $manualPagination = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Add additional data first if provided (e.g., summary before data)
        if ($additionalData !== null) {
            $response = array_merge($response, $additionalData);
        }

        // Check if Laravel Paginator or manual array
        if (is_object($data) && method_exists($data, 'items')) {
            // Laravel Paginator
            $response['data'] = $data->items();

            $response['pagination'] = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
            ];
        } else {
            // Manual array pagination
            $response['data'] = $data;

            if ($manualPagination) {
                $total = $manualPagination['total'];
                $perPage = $manualPagination['per_page'];
                $currentPage = $manualPagination['current_page'];
                $lastPage = (int) ceil($total / $perPage);
                $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : null;
                $to = min($currentPage * $perPage, $total);

                $response['pagination'] = [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $to,
                    'next_page_url' => $manualPagination['next_url'] ?? ($currentPage < $lastPage ? url()->current() . '?page=' . ($currentPage + 1) : null),
                    'prev_page_url' => $manualPagination['prev_url'] ?? ($currentPage > 1 ? url()->current() . '?page=' . ($currentPage - 1) : null),
                ];
            }
        }

        return response()->json($response, $status);
    }

    public function respond($data)
    {
        return response()->json($data);
    }
}
