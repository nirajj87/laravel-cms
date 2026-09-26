<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class InvoiceDocument
{
    public function html(Tenant $tenant, Order $order): string
    {
        $order->loadMissing(['items', 'customer']);

        return View::make('invoices.order', [
            'tenant' => $tenant,
            'order' => $order,
        ])->render();
    }

    public function pdfBinary(Tenant $tenant, Order $order): string
    {
        $html = $this->html($tenant, $order);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4')
                ->output();
        }

        return $html;
    }

    public function attachmentName(Order $order, string $binary): string
    {
        return str_starts_with($binary, '%PDF')
            ? 'invoice-'.$order->number.'.pdf'
            : 'invoice-'.$order->number.'.html';
    }

    public function download(Tenant $tenant, Order $order): Response
    {
        $binary = $this->pdfBinary($tenant, $order);
        $filename = $this->attachmentName($order, $binary);
        $mime = str_starts_with($binary, '%PDF') ? 'application/pdf' : 'text/html; charset=UTF-8';

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
