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
                'isbn' => ['size:13'],
            ],
            [
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
                'error' => 'API通信エラーが発生しました。',
            ], 504);

        } catch (RequestException $e) {
            if ($e->response->status() === 429) {
                return response()->json([
                    'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
                ], 429);
            }

            return response()->json([
                'error' => 'API通信エラーが発生しました。',
            ], 502);
        }
    }
}
