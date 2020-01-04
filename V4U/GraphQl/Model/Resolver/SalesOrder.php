<?php

declare(strict_types=1);

namespace V4U\GraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Order sales field resolver, used for GraphQL request processing
 */
class SalesOrder implements ResolverInterface
{
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $salesId = $this->getSalesId($args);
        $salesData = $this->getSalesData($salesId);

        return $salesData;
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getSalesId(array $args): int
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"sales id should be specified'));
        }

        return (int)$args['id'];
    }

    /**
     * @param int $orderId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getSalesData(int $orderId): array
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $shippingAddressArray = [];
            $shippingAddressArray['shipping_name'] = $order->getShippingAddress()->getData('firstname').' '.$order->getShippingAddress()->getData('lastname');
            $shippingAddressArray['city'] = $order->getShippingAddress()->getData('city');
            $shippingAddressArray['postcode'] = $order->getShippingAddress()->getData('postcode');
            $shippingAddressArray['state'] = $order->getShippingAddress()->getData('region');
            $orderData = [
                'increment_id' => $order->getIncrementId(),
                'grand_total' => $order->getGrandTotal(),
                'customer_name' => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                'is_guest_customer' => !empty($order->getCustomerIsGuest()) ? 1 : 0,
                'address_array' => $shippingAddressArray
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $orderData;
    }
}