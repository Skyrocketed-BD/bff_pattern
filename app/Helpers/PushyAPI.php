<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Http;

class PushyAPI
{
    public static function sendPushNotification($data, $to, $options = [])
    {
        $apiKey = env('PUSHY_SECRET_API_KEY', 'YOUR_DEFAULT_API_KEY'); // lebih baik ambil dari .env

        $post = $options ?: [];
        $post['to'] = $to;
        $post['data'] = $data;

        $headers = ['Content-Type: application/json'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/push?api_key=' . $apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        $response = @json_decode($result);

        if (isset($response->error)) {
            throw new Exception('Pushy API returned an error: ' . $response->error);
        }

        return $response;
    }

    public static function sendNotification($token, $title, $message, $url, $image)
    {
        $apiKey = env('PUSHY_SECRET_API_KEY'); // Tambahkan di .env

        $payload = [
            'to' => $token,
            'data' => [
                'title'   => $title,
                'message' => $message,
                'url'     => $url,
                'image'   => $image,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://api.pushy.me/push?api_key={$apiKey}", $payload);

        $responseBody = $response->json();

        if ($response->failed()) {
            throw new Exception('Notification Error: ' . $responseBody['error']);
        }

        return $responseBody;
    }
}
