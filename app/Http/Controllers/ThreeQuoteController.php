<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ThreeQuote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThreeQuoteController extends Controller
{
    public function download(Request $request, int $quoteId): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $quote = ThreeQuote::query()
            ->whereKey($quoteId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless($quote->pdf_path, 404);
        abort_unless(Storage::disk('public')->exists($quote->pdf_path), 404);

        $sceneName = data_get($quote->quotation, 'scene_name');
        $baseName = $sceneName ? preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $sceneName) : null;
        $baseName = trim((string) $baseName, '-');
        $baseName = $baseName !== '' ? $baseName : 'escena-3d';

        return response()->file(
            Storage::disk('public')->path($quote->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="cotizacion-'.$baseName.'-'.$quote->id.'.pdf"'
            ]
        );
    }

    public function send(Request $request, int $quoteId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $quote = ThreeQuote::query()
            ->whereKey($quoteId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($quote->status === 'sold') {
            return back()->with('status', 'Esta cotización ya fue convertida a venta.');
        }

        if (!$quote->pdf_path || !Storage::disk('public')->exists($quote->pdf_path)) {
            return back()->with('status', 'No se encontró el PDF de esta cotización. Genera la cotización nuevamente.');
        }

        if ($quote->status !== 'sent') {
            $quote->status = 'sent';
            $quote->sent_at = now();
            $quote->save();
        }

        return back()->with('status', 'Cotización enviada al administrador.');
    }
}
