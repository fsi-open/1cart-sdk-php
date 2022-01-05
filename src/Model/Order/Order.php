<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Order;

use DateTimeImmutable;
use Money\Money;
use OneCart\Api\Model\EmailAddress;
use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\InvoiceData;
use OneCart\Api\Model\Person;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Order
{
    private UuidInterface $id;
    private string $number;
    private DateTimeImmutable $createdAt;
    private EmailAddress $customer;
    private ?DateTimeImmutable $cancelledAt;
    private ?string $paymentType;
    private ?string $shippingType;
    private Money $total;
    private Money $totalWithShipping;
    private Money $totalWithShippingWithoutDiscount;
    private ?string $paymentState;
    private ?string $shippingState;
    private ?string $comments;
    private ?Person $contactPerson;
    private ?InvoiceData $invoiceData;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self(
            Uuid::fromString($data['id']),
            $data['number'],
            new DateTimeImmutable($data['created_at']),
            new EmailAddress($data['customer']['email'] ?? ''),
            (null !== ($data['cancelled_at'] ?? null)) ? new DateTimeImmutable($data['cancelled_at']) : null,
            $data['payment_type'] ?? null,
            $data['shipping_type'] ?? null,
            FormattedMoney::fromData($data['total'] ?? []),
            FormattedMoney::fromData($data['total_with_shipping'] ?? []),
            FormattedMoney::fromData($data['total_with_shipping_without_discount'] ?? []),
            $data['payment_state'] ?? null,
            $data['shipping_state'] ?? null,
            $data['comments'] ?? null,
            (null !== ($data['contact_person'] ?? null)) ? Person::fromData($data['contact_person'] ?? []) : null,
            (null !== ($data['invoice_data'] ?? null)) ? InvoiceData::fromData($data['invoice_data'] ?? []) : null
        );
    }

    public function __construct(
        UuidInterface $id,
        string $number,
        DateTimeImmutable $createdAt,
        EmailAddress $customer,
        ?DateTimeImmutable $cancelledAt,
        ?string $paymentType,
        ?string $shippingType,
        Money $total,
        Money $totalWithShipping,
        Money $totalWithShippingWithoutDiscount,
        ?string $paymentState,
        ?string $shippingState,
        ?string $comments,
        ?Person $contactPerson,
        ?InvoiceData $invoiceData
    ) {
        $this->id = $id;
        $this->number = $number;
        $this->createdAt = $createdAt;
        $this->customer = $customer;
        $this->cancelledAt = $cancelledAt;
        $this->paymentType = $paymentType;
        $this->shippingType = $shippingType;
        $this->total = $total;
        $this->totalWithShipping = $totalWithShipping;
        $this->totalWithShippingWithoutDiscount = $totalWithShippingWithoutDiscount;
        $this->paymentState = $paymentState;
        $this->shippingState = $shippingState;
        $this->comments = $comments;
        $this->contactPerson = $contactPerson;
        $this->invoiceData = $invoiceData;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCustomer(): EmailAddress
    {
        return $this->customer;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function getShippingType(): ?string
    {
        return $this->shippingType;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getTotalWithShipping(): Money
    {
        return $this->totalWithShipping;
    }

    public function getTotalWithShippingWithoutDiscount(): Money
    {
        return $this->totalWithShippingWithoutDiscount;
    }

    public function getPaymentState(): ?string
    {
        return $this->paymentState;
    }

    public function getShippingState(): ?string
    {
        return $this->shippingState;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getContactPerson(): ?Person
    {
        return $this->contactPerson;
    }

    public function getInvoiceData(): ?InvoiceData
    {
        return $this->invoiceData;
    }
}
