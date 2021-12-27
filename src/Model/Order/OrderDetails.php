<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Order;

use OneCart\Api\Model\Payment\Payment;
use OneCart\Api\Model\Shipping\Shipment;

final class OrderDetails
{
    private Order $order;
    /**
     * @var array<OrderItem>
     */
    private array $items;
    /**
     * @var array<Payment>
     */
    private array $payments;
    /**
     * @var array<Shipment>
     */
    private array $shipments;

    /**
     * @param Order $order
     * @param array<OrderItem> $items
     * @param array<Payment> $payments
     * @param array<Shipment> $shipments
     */
    public function __construct(Order $order, array $items, array $payments, array $shipments)
    {
        $this->order = $order;
        $this->items = $items;
        $this->payments = $payments;
        $this->shipments = $shipments;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return array<OrderItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return array<Payment>
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @return array<Shipment>
     */
    public function getShipments(): array
    {
        return $this->shipments;
    }
}
