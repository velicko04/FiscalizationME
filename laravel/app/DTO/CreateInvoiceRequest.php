<?php

namespace App\DTO;

use App\Enums\InvoiceType;
use App\Enums\TypeOfInvoice;
use App\Enums\PaymentMethodType;

class CreateInvoiceRequest
{
    public InvoiceType $invoiceType;
    public TypeOfInvoice $typeOfInvoice;
    public int $orderNumber;
    public PaymentMethodType $paymentMethod;
    public ?string $bankAccountNumber = null;
    public ?string $paymentDeadline = null;

    public BuyerDTO $buyer;
    /** @var InvoiceItemDTO[] */
    public array $items = [];
    public ?string $note = null;

    public ?OriginalInvoiceDTO $originalInvoice = null;
}

?>