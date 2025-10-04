<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class HelperService
{
    /**
     * Format phone number to a standard format.
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // If it starts with +998, keep as is
        if (str_starts_with($cleaned, '+998')) {
            return $cleaned;
        }
        
        // If it starts with 998, add +
        if (str_starts_with($cleaned, '998')) {
            return '+' . $cleaned;
        }
        
        // If it's a 9-digit number, assume it's Uzbek and add +998
        if (strlen($cleaned) === 9 && str_starts_with($cleaned, '9')) {
            return '+998' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(string $prefix = '', int $length = 8): string
    {
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random($length));
        
        return $prefix . $timestamp . $random;
    }

    /**
     * Calculate monthly payment for installments.
     */
    public static function calculateMonthlyPayment(float $totalAmount, float $prepayment, int $months, float $interestRate = 0): float
    {
        $loanAmount = $totalAmount - $prepayment;
        
        if ($loanAmount <= 0) {
            return 0;
        }
        
        if ($interestRate > 0) {
            $monthlyRate = $interestRate / 100 / 12;
            $monthlyPayment = $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
        } else {
            $monthlyPayment = $loanAmount / $months;
        }
        
        return round($monthlyPayment, 2);
    }

    /**
     * Calculate total interest for installments.
     */
    public static function calculateTotalInterest(float $loanAmount, int $months, float $interestRate): float
    {
        if ($interestRate <= 0) {
            return 0;
        }
        
        $monthlyPayment = self::calculateMonthlyPayment($loanAmount, 0, $months, $interestRate);
        $totalPayments = $monthlyPayment * $months;
        
        return round($totalPayments - $loanAmount, 2);
    }

    /**
     * Format currency amount.
     */
    public static function formatCurrency(float $amount, string $currency = 'UZS'): string
    {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }

    /**
     * Parse full name into components.
     */
    public static function parseFullName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        
        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[count($parts) - 1] ?? '',
            'middle_name' => count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : ''
        ];
    }

    /**
     * Generate slug from text.
     */
    public static function generateSlug(string $text): string
    {
        return Str::slug($text);
    }

    /**
     * Check if date is within range.
     */
    public static function isDateInRange(string $date, string $startDate, string $endDate): bool
    {
        $checkDate = Carbon::parse($date);
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $checkDate->between($start, $end);
    }

    /**
     * Calculate age from birth date.
     */
    public static function calculateAge(string $birthDate): int
    {
        return Carbon::parse($birthDate)->age;
    }

    /**
     * Check if person is adult (18+).
     */
    public static function isAdult(string $birthDate): bool
    {
        return self::calculateAge($birthDate) >= 18;
    }

    /**
     * Format date for display.
     */
    public static function formatDate(string $date, string $format = 'd.m.Y'): string
    {
        return Carbon::parse($date)->format($format);
    }

    /**
     * Format datetime for display.
     */
    public static function formatDateTime(string $datetime, string $format = 'd.m.Y H:i'): string
    {
        return Carbon::parse($datetime)->format($format);
    }

    /**
     * Get time difference in human readable format.
     */
    public static function getTimeDifference(string $datetime): string
    {
        return Carbon::parse($datetime)->diffForHumans();
    }

    /**
     * Sanitize input string.
     */
    public static function sanitizeString(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Generate password hash.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password against hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate random string.
     */
    public static function generateRandomString(int $length = 10): string
    {
        return Str::random($length);
    }

    /**
     * Generate numeric code.
     */
    public static function generateNumericCode(int $length = 6): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    /**
     * Mask sensitive data.
     */
    public static function maskSensitiveData(string $data, int $visibleChars = 4): string
    {
        $length = strlen($data);
        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }
        
        $masked = str_repeat('*', $length - $visibleChars);
        return $masked . substr($data, -$visibleChars);
    }

    /**
     * Convert array to CSV string.
     */
    public static function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Check if string is JSON.
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get file extension from filename.
     */
    public static function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Convert bytes to human readable format.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate UUID v4.
     */
    public static function generateUuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Validate Uzbek passport series and number.
     */
    public static function validateUzbekPassport(string $series, string $number): bool
    {
        // Uzbek passport series: 2 letters
        $seriesPattern = '/^[A-Z]{2}$/';
        // Uzbek passport number: 7 digits
        $numberPattern = '/^[0-9]{7}$/';
        
        return preg_match($seriesPattern, $series) && preg_match($numberPattern, $number);
    }

    /**
     * Format Uzbek passport.
     */
    public static function formatUzbekPassport(string $series, string $number): string
    {
        return strtoupper($series) . $number;
    }
}