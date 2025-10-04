<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const PHONE_LENGTH = 12;
    private const COUNTRY_CODE = '998';
    private const SMS_API_URL = 'https://notify.eskiz.uz/api/message/sms/send';
    
    private Client $client;
    private string $token;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->token = config('services.sms.token', env('SMS_TOKEN'));
    }

    /**
     * Send SMS message to a phone number.
     */
    public function send(string $phone, string $message): bool
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            if (!$this->isValidMessage($message)) {
                Log::warning('Invalid SMS message', ['message' => $message]);
                return false;
            }

            $response = $this->client->request('POST', self::SMS_API_URL, [
                'form_params' => [
                    'mobile_phone' => $formattedPhone,
                    'message' => $message
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($this->isSuccessfulResponse($data)) {
                Log::info('SMS sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $data
                ]);
                return true;
            }

            Log::error('SMS sending failed', [
                'phone' => $formattedPhone,
                'response' => $data
            ]);
            return false;

        } catch (GuzzleException $e) {
            Log::error('SMS service error', [
                'phone' => $phone,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        }
    }

    /**
     * Send bulk SMS messages.
     */
    public function sendBulk(array $recipients): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $phone = $recipient['phone'] ?? '';
            $message = $recipient['message'] ?? '';
            
            $results[] = [
                'phone' => $phone,
                'success' => $this->send($phone, $message)
            ];
        }
        
        return $results;
    }

    /**
     * Format phone number to international format.
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // If phone number is not 12 digits, add country code
        if (strlen($phone) !== self::PHONE_LENGTH) {
            $phone = self::COUNTRY_CODE . substr($phone, -9);
        }
        
        return $phone;
    }

    /**
     * Validate SMS message content.
     */
    private function isValidMessage(string $message): bool
    {
        return !empty(trim($message)) && strlen($message) <= 1000;
    }

    /**
     * Check if SMS API response indicates success.
     */
    private function isSuccessfulResponse(?array $data): bool
    {
        return $data !== null && 
               isset($data['status']) && 
               $data['status'] === 'success';
    }

    /**
     * Get SMS service status.
     */
    public function getStatus(): array
    {
        try {
            $response = $this->client->request('GET', 'https://notify.eskiz.uz/api/auth/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ]
            ]);

            return [
                'available' => true,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];
        } catch (GuzzleException $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
