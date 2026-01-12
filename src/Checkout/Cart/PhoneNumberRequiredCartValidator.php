<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Checkout\Cart;

use KeszlerShippingPhoneRequirement\Service\PhoneNumberValidator;
use KeszlerShippingPhoneRequirement\Service\ShippingMethodPhoneRequirement;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PhoneNumberRequiredCartValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly ShippingMethodPhoneRequirement $phoneRequirement,
        private readonly PhoneNumberValidator $phoneNumberValidator
    )
    {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (!$this->phoneRequirement->isPhoneRequiredForContext($context)) {
            return;
        }

        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            return;
        }

        $phoneNumber = $this->getPhoneNumber($customer);
        if ($phoneNumber === null || trim($phoneNumber) === '') {
            $errors->add(new PhoneNumberRequiredError());
            return;
        }

        if (!$this->phoneNumberValidator->hasDigits($phoneNumber)) {
            $errors->add(new PhoneNumberInvalidError());
        }
    }

    private function getPhoneNumber(CustomerEntity $customer): ?string
    {
        $address = $customer->getActiveShippingAddress();
        if (!$address instanceof CustomerAddressEntity) {
            $address = $customer->getActiveBillingAddress();
        }

        return $address?->getPhoneNumber();
    }
}
