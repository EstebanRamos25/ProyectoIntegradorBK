<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FacturaController extends Controller
{
    /**
     * Genera la factura para una venta y redirige al PDF.
     */
    public function generate(Venta $venta): RedirectResponse
    {
        // Cargar relaciones necesarias
        $venta->load(['usuario', 'producto', 'factura']);

        // Si ya existe factura, ir directo al PDF
        if ($venta->factura) {
            return redirect()->route('facturas.pdf', $venta->factura);
        }

        // Crear la factura en BD
        $factura = Factura::generarDesdeVenta($venta);

        // Generar el PDF y guardarlo en storage
        $this->generarPDF($factura->fresh(['venta.usuario', 'venta.producto']));

        return redirect()
            ->route('facturas.pdf', $factura)
            ->with('success', "Factura {$factura->numero_factura} generada exitosamente.");
    }

    /**
     * Muestra el PDF de la factura en el navegador (inline).
     */
    public function show(Factura $factura): Response
    {
        $factura->load(['venta.usuario', 'venta.producto', 'venta.inventariosDescontados.inventario']);

        $pdf = Pdf::loadView('facturas.pdf', ['factura' => $factura])
            ->setPaper('a4', 'portrait');

        return $pdf->stream("factura-{$factura->numero_factura}.pdf");
    }

    // ──────────────────────────────────────────────────────────────
    // Privado: genera y guarda el PDF en disco
    // ──────────────────────────────────────────────────────────────

    private function generarPDF(Factura $factura): void
    {
        $pdf  = Pdf::loadView('facturas.pdf', ['factura' => $factura])
            ->setPaper('a4', 'portrait');

        $path = "facturas/{$factura->numero_factura}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $factura->update(['pdf_path' => $path]);
    }
}
