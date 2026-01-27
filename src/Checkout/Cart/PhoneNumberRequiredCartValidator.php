<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Checkout\Cart;

use KeszlerShippingPhoneRequirement\Service\PhoneNumberValidator;
use KeszlerShippingPhoneRequirement\Service\ShippingMethodPhoneRequirement;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PhoneNumberRequiredCartValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly ShippingMethodPhoneRequirement $phoneRequirement,
        private readonly PhoneNumberValidator $phoneNumberValidator,
        private readonly SystemConfigService $systemConfigService,
        private readonly AbstractTranslator $translator
    ) {
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
        $label = $this->getConfiguredLabel($context);
        if ($phoneNumber === null || trim($phoneNumber) === '') {
            $errors->add(new PhoneNumberRequiredError($label));
            return;
        }

        if (!$this->phoneNumberValidator->hasDigits($phoneNumber)) {
            $errors->add(new PhoneNumberInvalidError($label));
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

    private function getConfiguredLabel(SalesChannelContext $context): string
    {
        $snippetKey = $this->systemConfigService->get(
            'KeszlerShippingPhoneRequirement.config.phoneNumberLabelSnippet',
            $context->getSalesChannelId()
        );

        if (!is_string($snippetKey) || $snippetKey === '') {
            $snippetKey = 'keszlerShippingPhoneRequirement.phoneNumberLabelPhone';
        }

        return $this->translator->trans(
            $snippetKey,
            [],
            null,
            $context->getLanguageInfo()->localeCode
        );
    }
}
