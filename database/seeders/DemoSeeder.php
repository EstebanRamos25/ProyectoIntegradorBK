<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ThreeInteractionEvent;
use App\Models\ThreeQuote;
use App\Models\ThreeScene;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run()
    {
        // 1. Obtener o crear categorías
        $categorias = [
            'Porcelanatos' => Categoria::firstOrCreate(['Nombre' => 'Porcelanatos'], ['Descripcion' => 'Alta calidad', 'Tipo_Material' => 'Porcelanato', 'Resistencia' => 'Alta']),
            'Cerámicas' => Categoria::firstOrCreate(['Nombre' => 'Cerámicas'], ['Descripcion' => 'Variedad de diseños', 'Tipo_Material' => 'Cerámica', 'Resistencia' => 'Media']),
            'Maderas y Flotantes' => Categoria::firstOrCreate(['Nombre' => 'Maderas y Flotantes'], ['Descripcion' => 'Pisos laminados', 'Tipo_Material' => 'Madera', 'Resistencia' => 'Media']),
        ];

        // 2. Crear 30 Productos
        $productosData = [
            ['Porcelanato Carrara Brillante', 'Porcelanatos', 150, 100],
            ['Porcelanato Calacatta Gold', 'Porcelanatos', 180, 120],
            ['Porcelanato Onix Beige', 'Porcelanatos', 145, 95],
            ['Porcelanato Gris Mate', 'Porcelanatos', 130, 85],
            ['Porcelanato Nero Marquina', 'Porcelanatos', 190, 130],
            ['Porcelanato Travertino', 'Porcelanatos', 140, 90],
            ['Porcelanato Blanco Puro', 'Porcelanatos', 160, 105],
            ['Porcelanato Cemento Pulido', 'Porcelanatos', 135, 88],
            ['Porcelanato Oxidado', 'Porcelanatos', 155, 98],
            ['Porcelanato Madera Oscura', 'Porcelanatos', 170, 110],

            ['Cerámica Blanca Clásica', 'Cerámicas', 80, 50],
            ['Cerámica Beige Texturada', 'Cerámicas', 85, 55],
            ['Cerámica Gris Piedra', 'Cerámicas', 90, 60],
            ['Cerámica Azul Piscina', 'Cerámicas', 75, 45],
            ['Cerámica Verde Esmeralda', 'Cerámicas', 95, 65],
            ['Cerámica Geométrica', 'Cerámicas', 110, 75],
            ['Cerámica Mosaico Vintage', 'Cerámicas', 105, 70],
            ['Cerámica Roja Rústica', 'Cerámicas', 88, 58],
            ['Cerámica Negra Mate', 'Cerámicas', 120, 80],
            ['Cerámica Floral', 'Cerámicas', 100, 68],

            ['Piso Laminado Roble Claro', 'Maderas y Flotantes', 115, 75],
            ['Piso Laminado Nogal', 'Maderas y Flotantes', 125, 85],
            ['Piso Laminado Cerezo', 'Maderas y Flotantes', 130, 90],
            ['Piso Laminado Gris Ceniza', 'Maderas y Flotantes', 120, 80],
            ['Piso Laminado Blanco Nórdico', 'Maderas y Flotantes', 140, 95],
            ['Piso Flotante Pino', 'Maderas y Flotantes', 105, 70],
            ['Piso Flotante Haya', 'Maderas y Flotantes', 110, 72],
            ['Piso Flotante Bambú', 'Maderas y Flotantes', 150, 100],
            ['Piso Flotante Vintage', 'Maderas y Flotantes', 135, 88],
            ['Madera Sólida Teka', 'Maderas y Flotantes', 250, 180],
        ];

        $productos = [];
        $i = 1;
        foreach ($productosData as $data) {
            $cat = $categorias[$data[1]];
            $prod = Producto::updateOrCreate(
                ['Nombre' => $data[0]],
                [
                    'Descripcion' => 'Producto de prueba ' . $i,
                    'Precio' => $data[2],
                    'Costo_M2' => $data[3],
                    'M2_Por_Caja' => 2.5,
                    'Piezas_Por_Caja' => 10,
                    'Unidad_Venta' => 'Caja',
                    'Ancho_Pieza_Cm' => 50,
                    'Largo_Pieza_Cm' => 50,
                    'Marca' => 'DemoBrand',
                    'Modelo' => 'Mod-'.$i,
                    'Stock_Minimo' => 20,
                    'categoria_id' => $cat->id,
                ]
            );
            $productos[] = $prod;
            $i++;

            // 3. Crear Inventario (Lotes)
            // Lógica para que el MLDecisionReport tenga datos interesantes
            // Productos estrella (muchas ventas luego, alto stock)
            // Riesgos de quiebre (muchas ventas luego, bajo stock)
            // Excesos (pocas ventas luego, mucho stock)
            
            $cajas = 0;
            if ($i <= 5) {
                $cajas = 500; // Estrella
            } elseif ($i > 5 && $i <= 10) {
                $cajas = 10; // Riesgo
            } elseif ($i > 10 && $i <= 15) {
                $cajas = 600; // Exceso
            } else {
                $cajas = rand(30, 80); // Neutro
            }

            Inventario::updateOrCreate(
                ['Codigo_Lote' => 'LOTE-DEMO-'.$i],
                [
                    'Cajas_Entrada' => $cajas + 50,
                    'Cajas_Disponibles' => $cajas,
                    'Costo_M2' => $data[3],
                    'Ubicacion' => 'Almacén Central',
                    'Estado' => 'Disponible',
                    'Fecha_Ingreso' => now()->subMonths(3),
                    'producto_id' => $prod->id,
                ]
            );
        }

        // 4. Crear un User y una Escena para las ventas/cotizaciones
        $user = User::first();
        if (!$user) {
            $user = User::create(['name' => 'Demo User', 'email' => 'demo@demo.com', 'password' => bcrypt('password')]);
        }
        $scene = ThreeScene::firstOrCreate(
            ['name' => 'Escena de Prueba Demo'],
            ['user_id' => $user->id, 'data' => []]
        );

        // 5. Generar Ventas e Interacciones en los últimos 60 días
        $start = now()->subDays(60);
        $totalDays = 60;

        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = (clone $start)->addDays($day);
            
            // Cuántas interacciones por día? (Más para los productos estrella/riesgo, menos para los excesos)
            $numInteractions = rand(5, 15);

            for ($j = 0; $j < $numInteractions; $j++) {
                // Seleccionar un producto
                // Truco: Los primeros 10 productos son muy populares (Estrellas y Riesgos)
                if (rand(1, 100) <= 70) {
                    $prod = $productos[rand(0, 9)];
                } else {
                    $prod = $productos[rand(10, 29)];
                }

                // Simular interacción 3D
                ThreeInteractionEvent::create([
                    'user_id' => $user->id,
                    'three_scene_id' => $scene->id,
                    'producto_id' => $prod->id,
                    'categoria_id' => $prod->categoria_id,
                    'event_type' => 'material_select',
                    'value' => 1.0,
                    'created_at' => $currentDate,
                ]);

                // 20% de probabilidad de que la interacción se convierta en una Cotización y luego Venta
                if (rand(1, 100) <= 20) {
                    // Cotización
                    $quote = ThreeQuote::create([
                        'three_scene_id' => $scene->id,
                        'user_id' => $user->id,
                        'producto_id' => $prod->id,
                        'boxes_required' => rand(5, 20),
                        'area_m2' => rand(12, 50),
                        'total' => rand(1500, 5000),
                        'status' => 'sold',
                        'quotation' => [], // Campo requerido
                        'created_at' => $currentDate,
                    ]);

                    ThreeInteractionEvent::create([
                        'user_id' => $user->id,
                        'three_scene_id' => $scene->id,
                        'producto_id' => $prod->id,
                        'categoria_id' => $prod->categoria_id,
                        'event_type' => 'quote_generate',
                        'value' => 1.0,
                        'created_at' => $currentDate,
                    ]);

                    // Venta
                    $cajasVendidas = $quote->boxes_required;
                    $areaVendida = $cajasVendidas * 2.5;
                    Venta::create([
                        'Fecha' => $currentDate,
                        'Total' => $quote->total,
                        'Origen' => '3d_sale',
                        'three_quote_id' => $quote->id,
                        'usuario_id' => $user->id,
                        'producto_id' => $prod->id,
                        'Area_M2' => $areaVendida,
                        'Precio_M2' => $prod->Precio,
                        'Subtotal' => $quote->total,
                        'Costo_M2' => $prod->Costo_M2,
                        'Costo_Total' => $prod->Costo_M2 * $areaVendida,
                        'created_at' => $currentDate,
                    ]);
                }
            }
        }
    }
}
