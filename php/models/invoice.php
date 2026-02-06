<?php

class Invoice
{
    public int $id;
    public string $invoiceNumber;
    public string $invoiceType;
    public string $typeOfInvoice;
    public DateTime $issuedAt;
    public string $enuCode;
    public bool $isIssuerInVat;

    public float $totalPriceWithoutVat;
    public float $totalVatAmount;
    public float $totalPrice;
    public float $totalPriceToPay;

    public ?string $note;
    public ?string $bankAccountNumber;
    public ?string $paymentDeadline;

    public Partner $seller;
    public Partner $buyer;

    /** @var InvoiceItem[] */
    public array $items = [];

    /** @var PaymentMethod[] */
    public array $payments = [];

    /** @var SameTax[] */
    public array $sameTaxes = [];

    public static function fromDb(array $row): self
    {
        $i = new self();
        $i->id = $row['id'];
        $i->invoiceNumber = $row['invoice_number'];
        $i->invoiceType = $row['invoice_type'];
        $i->typeOfInvoice = $row['type_of_invoice'];
        $i->issuedAt = new DateTime($row['issued_at']);
        $i->enuCode = $row['enu_code'];
        $i->isIssuerInVat = (bool)$row['is_issuer_in_vat'];
        $i->totalPriceWithoutVat = (float)$row['total_price_without_vat'];
        $i->totalVatAmount = (float)$row['total_vat_amount'];
        $i->totalPrice = (float)$row['total_price'];
        $i->totalPriceToPay = (float)$row['total_price_to_pay'];
        $i->note = $row['note'];
        $i->bankAccountNumber = $row['bank_account_number'];
        $i->paymentDeadline = $row['payment_deadline'];
        return $i;
    }
}


?>