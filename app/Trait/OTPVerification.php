<?php

namespace App\Trait;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Vonage\Client\Credentials\Basic;



class OTPVerification
{

    protected static $baseUrl = 'https://www.dreams.sa/index.php/api/sendsms/';
    protected static $user = 'Hawyytawsel';
    protected static $secretKey = '7c76ff63f8f4157ecb93cb7cb1fcc9e580fee430aea920d50bc68708f7f42ced';
    protected static $sender = 'Hawy-Tawsel'; // لازم تكون مفعّلة من لوحة التحكم

    public static function sendOTP($phone, $otp)
    {
        $message = "رمز التحقق الخاص بك هو: $otp";

        try {
            $response = Http::get(self::$baseUrl, [
                'user' => self::$user,
                'secret_key' => self::$secretKey,
                'to' => $phone,
                'message' => $message,
                'sender' => self::$sender,
            ]);

            Log::info('Dreams SMS Response:', ['response' => $response->body()]);

            return str_contains($response->body(), 'Result') || str_contains($response->body(), 'SMS_ID')
                ? ['success' => true, 'response' => $response->body()]
                : ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Dreams SMS Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
}