<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Error\Error;

class PhoneNumberInvalidError extends Error
{
    private const ERROR_ID = 'keszler-shipping-phone-invalid';

    public function __construct(private readonly string $label)
    {
        parent::__construct('Phone number must contain at least one digit.');
    }

    public function getId(): string
    {
        return self::ERROR_ID;
    }

    public function getMessageKey(): string
    {
        return 'phoneNumberInvalidForShippingMethod';
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return [
            'label' => $this->label,
        ];
    }
}
