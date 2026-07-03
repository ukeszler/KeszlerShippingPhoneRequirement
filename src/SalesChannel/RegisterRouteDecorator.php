<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\SalesChannel;

use KeszlerShippingPhoneRequirement\Service\ShippingMethodPhoneRequirement;
use KeszlerShippingPhoneRequirement\Service\PhoneNumberValidator;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegisterRouteDecorator extends AbstractRegisterRoute
{
    public function __construct(
        private readonly AbstractRegisterRoute $decorated,
        private readonly ShippingMethodPhoneRequirement $phoneRequirement,
        private readonly DataValidator $validator,
        private readonly PhoneNumberValidator $phoneNumberValidator,
        private readonly RequestStack $requestStack
    ) {
    }

    public function getDecorated(): AbstractRegisterRoute
    {
        return $this->decorated;
    }

    public function register(
        RequestDataBag $data,
        SalesChannelContext $context,
        bool $validateStorefrontUrl = true,
        ?DataValidationDefinition $additionalValidationDefinitions = null
    ): CustomerResponse {
        $this->validatePhoneNumber($data, $context);

        return $this->decorated->register($data, $context, $validateStorefrontUrl, $additionalValidationDefinitions);
    }

    private function validatePhoneNumber(RequestDataBag $data, SalesChannelContext $context): void
    {
        if ($this->isPayPalExpressPrepareCheckoutRequest()) {
            return;
        }

        if (!$this->phoneRequirement->isPhoneRequiredForContext($context)) {
            return;
        }

        $addressKey = $this->resolveAddressKey($data);
        if ($addressKey === null) {
            return;
        }

        $address = $data->get($addressKey);
        if (!$address instanceof DataBag) {
            return;
        }

        if ($this->hasValidPhoneNumber($address)) {
            return;
        }

        $definition = new DataValidationDefinition('keszler_shipping_phone_requirement');
        $addressDefinition = new DataValidationDefinition();
        $phoneNumber = $address->get('phoneNumber');
        if (!is_string($phoneNumber) || trim($phoneNumber) === '') {
            $addressDefinition->add('phoneNumber', new NotBlank(message: 'VIOLATION::PHONE_NUMBER_IS_BLANK_ERROR'));
        } else {
            $addressDefinition->add('phoneNumber', new Regex(pattern: '/\d/', message: 'VIOLATION::PHONE_NUMBER_INVALID_ERROR'));
        }
        $definition->addSub($addressKey, $addressDefinition);

        $this->validator->validate($data->all(), $definition);
    }

    private function resolveAddressKey(RequestDataBag $data): ?string
    {
        $shippingAddress = $data->get('shippingAddress');
        if ($shippingAddress instanceof DataBag) {
            return 'shippingAddress';
        }

        $billingAddress = $data->get('billingAddress');
        if ($billingAddress instanceof DataBag) {
            return 'billingAddress';
        }

        return null;
    }

    private function hasValidPhoneNumber(DataBag $address): bool
    {
        $phoneNumber = $address->get('phoneNumber');

        return is_string($phoneNumber) && $this->phoneNumberValidator->hasDigits($phoneNumber);
    }

    private function isPayPalExpressPrepareCheckoutRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        return \in_array($request->attributes->get('_route'), [
            'frontend.paypal.express.prepare_checkout',
            'store-api.paypal.express.prepare_checkout',
        ], true);
    }
}
