<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;

class PDFController extends Controller
{
    public function __invoke(Order $order)
    {
        $pdf = PDF::loadView('pdf', ['record' => $order]);

        return $pdf->download('document_pdf_' . $order->id . '.pdf');
    }
}
