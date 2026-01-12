<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Service;

class PhoneNumberValidator
{
    public function hasDigits(?string $phoneNumber): bool
    {
        if ($phoneNumber === null) {
            return false;
        }

        $phoneNumber = trim($phoneNumber);
        if ($phoneNumber === '') {
            return false;
        }

        return preg_match('/\d/', $phoneNumber) === 1;
    }
}
