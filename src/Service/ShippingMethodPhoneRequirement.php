<?php declare(strict_types=1);

namespace KeszlerShippingPhoneRequirement\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ShippingMethodPhoneRequirement
{
    public const CONFIG_KEY = 'KeszlerShippingPhoneRequirement.config.shippingMethodsRequiringPhoneNumber';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function isPhoneRequiredForContext(SalesChannelContext $context): bool
    {
        $requiredIds = $this->getRequiredShippingMethodIds($context->getSalesChannelId());
        if ($requiredIds === []) {
            return false;
        }

        return in_array($context->getShippingMethod()->getId(), $requiredIds, true);
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredShippingMethodIds(?string $salesChannelId): array
    {
        $value = $this->systemConfigService->get(self::CONFIG_KEY, $salesChannelId);

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn ($id): bool => is_string($id) && $id !== ''
        ));
    }
}
