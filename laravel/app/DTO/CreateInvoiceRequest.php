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

    // Novi atributi za FiscalXmlBuilder
    public $company; // može CompanyDTO ili model
    public $user;    // može UserDTO ili model

    // Za test možemo dodati polja za cijenu
    public ?float $total_price_to_pay = null;
    public ?float $total_price_without_vat = null;
    public ?float $total_vat_amount = null;
    public ?string $invoice_number = null;
    public ?\DateTime $issued_at = null;
    public ?string $iic = null;
    public ?string $iic_signature = null;
}