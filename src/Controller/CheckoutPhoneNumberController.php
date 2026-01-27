<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Controller;

use KeszlerShippingPhoneRequirement\Service\PhoneNumberValidator;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class CheckoutPhoneNumberController extends StorefrontController
{
    /**
     * @param EntityRepository<CustomerAddressEntity> $addressRepository
     */
    public function __construct(
        private readonly EntityRepository $addressRepository,
        private readonly PhoneNumberValidator $phoneNumberValidator,
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    #[Route(
        path: '/checkout/phone-number',
        name: 'frontend.checkout.phone-number.update',
        options: ['seo' => false],
        defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true],
        methods: ['POST']
    )]
    public function update(Request $request, SalesChannelContext $context, CustomerEntity $customer): RedirectResponse
    {
        $phoneNumber = trim((string) $request->request->get('phoneNumber'));
        $labelText = $this->getConfiguredLabel($context);

        if ($phoneNumber === '') {
            $this->addFlash(self::DANGER, $this->trans('checkout.phoneNumberRequiredForShippingMethod', ['%label%' => $labelText]));

            return $this->redirectToRoute('frontend.checkout.confirm.page');
        }

        if (!$this->phoneNumberValidator->hasDigits($phoneNumber)) {
            $this->addFlash(self::DANGER, $this->trans('checkout.phoneNumberInvalidForShippingMethod', ['%label%' => $labelText]));

            return $this->redirectToRoute('frontend.checkout.confirm.page');
        }

        $addressId = $this->resolveAddressId($customer, $context);
        if ($addressId === null) {
            $this->addFlash(self::DANGER, $this->trans('account.addressNotFound'));

            return $this->redirectToRoute('frontend.checkout.confirm.page');
        }

        $this->addressRepository->update([[
            'id' => $addressId,
            'phoneNumber' => $phoneNumber,
        ]], $context->getContext());

        $this->addFlash(self::SUCCESS, $this->trans('account.addressSaved'));

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    private function resolveAddressId(CustomerEntity $customer, SalesChannelContext $context): ?string
    {
        $addressId = $customer->getActiveShippingAddress()?->getId()
            ?? $customer->getActiveBillingAddress()?->getId();

        if ($addressId === null || !Uuid::isValid($addressId)) {
            return null;
        }

        $criteria = (new Criteria([$addressId]))->addFilter(
            new EqualsFilter('customerId', $customer->getId())
        );

        $result = $this->addressRepository->searchIds($criteria, $context->getContext());

        return $result->firstId();
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

        return $this->trans($snippetKey);
    }
}
