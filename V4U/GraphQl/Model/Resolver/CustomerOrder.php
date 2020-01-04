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
class CustomerOrder implements ResolverInterface
{
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Directory\Model\Currency $currencyFormat
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->priceCurrency = $priceCurrency;
        $this->currencyFormat =$currencyFormat;
    }

    /**
     * Get current store currency symbol with price
     * $price price value
     * true includeContainer
     * Precision value 2
     */
    public function getCurrencyFormat($price)
    {
        return $this->getCurrencySymbol() . number_format((float)$price, \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION);
    }

    /**
     * Get current store CurrencySymbol
     */
    public function getCurrencySymbol()
    {
        $symbol = $this->priceCurrency->getCurrencySymbol();
        return $symbol;
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
        $customerId = $this->getCustomerId($args);
        $salesData = $this->getSalesData($customerId);

        return $salesData;
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getCustomerId(array $args): int
    {
        if (!isset($args['customer_id'])) {
            throw new GraphQlInputException(__('"Customer id must be specified'));
        }

        return (int)$args['customer_id'];
    }

    /**
     * @param int $customerId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getSalesData(int $customerId): array
    {
        try {
            /* filter for all customer orders */
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId,'eq')->create();
            $orders = $this->orderRepository->getList($searchCriteria);

            $salesOrder = [];
            foreach($orders as $order) {
                $orderId = $order->getId();
                $salesOrder['allOrderRecords'][$orderId]['increment_id'] = $order->getIncrementId();
                $salesOrder['allOrderRecords'][$orderId]['customer_name'] = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
                $salesOrder['allOrderRecords'][$orderId]['grand_total'] = $this->getCurrencyFormat($order->getGrandTotal());
                $salesOrder['allOrderRecords'][$orderId]['created_at'] = $order->getCreatedAt();
                $salesOrder['allOrderRecords'][$orderId]['shipping_method'] = $order->getShippingMethod();
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $salesOrder;
    }
}