<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Error\Error;

class PhoneNumberRequiredError extends Error
{
    private const ERROR_ID = 'keszler-shipping-phone-required';

    public function __construct(private readonly string $label)
    {
        parent::__construct('Phone number is required for the selected shipping method.');
    }

    public function getId(): string
    {
        return self::ERROR_ID;
    }

    public function getMessageKey(): string
    {
        return 'phoneNumberRequiredForShippingMethod';
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
