<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');

            // Numeración: FAC-2026-0001
            $table->string('numero_factura', 20)->unique();

            // Emisor (empresa)
            $table->string('nit_emisor', 20)->default('1023456789');
            $table->string('razon_social_emisor')->default('Materiales 3D S.R.L.');

            // Cliente (99001 = Consumidor Final por defecto en Bolivia)
            $table->string('nit_cliente', 20)->default('99001');
            $table->string('nombre_cliente');

            // Financiero — IVA 13% incluido en precio, extraído
            $table->date('fecha_emision');
            $table->decimal('subtotal_sin_iva', 10, 2); // total / 1.13
            $table->decimal('iva_monto', 10, 2);        // total - (total / 1.13)
            $table->decimal('total', 10, 2);

            // Estado
            $table->string('estado', 20)->default('emitida'); // emitida | anulada

            // Códigos simulados de verificación SIN
            $table->string('codigo_autorizacion', 30);
            $table->string('codigo_control', 60);

            // PDF generado por DomPDF
            $table->string('pdf_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
