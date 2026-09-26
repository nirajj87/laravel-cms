<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\InvoiceDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Order $order,
        public Customer $customer,
        public ?string $plainPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice '.$this->order->number.' — '.$this->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-invoice',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $doc = app(InvoiceDocument::class);
        $binary = $doc->pdfBinary($this->tenant, $this->order);
        $name = $doc->attachmentName($this->order, $binary);
        $mime = str_starts_with($binary, '%PDF') ? 'application/pdf' : 'text/html';

        return [
            Attachment::fromData(fn () => $binary, $name)->withMime($mime),
        ];
    }
}
