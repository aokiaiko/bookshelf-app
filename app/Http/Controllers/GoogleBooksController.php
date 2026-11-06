<?php

namespace App\Http\Controllers;

use App\Services\GoogleBooksService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class GoogleBooksController extends Controller
{
    /**
     * ISBNから書籍情報を取得する。
     */
    public function search(
        string $isbn,
        GoogleBooksService $service
    ): JsonResponse {
        $validator = Validator::make(
            ['isbn' => $isbn],
            [
                'isbn' => ['string', 'size:13'],
            ],
            [
                'isbn.string' => 'ISBNは文字列で入力してください。',
                'isbn.size' => 'ISBNは13桁で入力してください。',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first('isbn'),
            ], 422);
        }

        try {
            $bookData = $service->search($isbn);

            return response()->json($bookData, 200);

        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);

        } catch (ConnectionException $e) {
            return response()->json([
                'error' => '書籍情報を取得できませんでした。',
            ], 504);

        } catch (RequestException $e) {
            return response()->json([
                'error' => '書籍情報を取得できませんでした。',
            ], 502);
        }
    }
}
