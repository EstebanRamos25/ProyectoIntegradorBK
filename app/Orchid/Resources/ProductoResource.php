<?php

namespace App\Orchid\Resources;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;
use App\Models\Categoria;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class ProductoResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Producto::class;

    public function fields(): array
    {
        return [
            Input::make('Nombre')->title('Nombre')->placeholder('Ingresa el nombre del producto'),
            Input::make('Descripcion')->title('Descripcion')->placeholder('Ingresa la descripcion del producto'),
            Input::make('Precio')
                ->type('number')
                ->title('Precio por m²')
                ->step(0.01)
                ->placeholder('Ej: 180.00'),
            Input::make('M2_Por_Caja')
                ->type('number')
                ->step(0.0001)
                ->title('Cobertura (m²) por caja')
                ->placeholder('Ej: 1.80'),
            Input::make('Piezas_Por_Caja')
                ->type('number')
                ->title('Piezas por caja')
                ->placeholder('Ej: 10'),
            Select::make('Unidad_Venta')
                ->title('Unidad de venta')
                ->options([
                    'caja' => 'Caja (cerrada)',
                    'm2' => 'm² (solo referencia)',
                ])
                ->empty('Selecciona')
                ->help('En cerámica normalmente se vende por caja cerrada, aunque el precio se cotice por m².'),
            Input::make('Ancho_Pieza_Cm')
                ->type('number')
                ->step(0.01)
                ->title('Ancho de pieza (cm)')
                ->placeholder('Ej: 45'),
            Input::make('Largo_Pieza_Cm')
                ->type('number')
                ->step(0.01)
                ->title('Largo de pieza (cm)')
                ->placeholder('Ej: 45'),
            Input::make('Marca')->title('Marca')->placeholder('Ingresa la marca del producto'),
            Input::make('Modelo')->title('Modelo')->placeholder('Ingresa el modelo del producto'),
            Input::make('Stock_Minimo')->type('number')->title('Stock Minimo')->placeholder('Ingresa el stock mínimo'),
            Select::make('categoria_id')->title('Categoria')->fromModel(Categoria::class, 'Nombre')->empty('Selecciona una categoria'),
            Upload::make('image')->title('Imagen')->groups('image')->acceptedFiles('image/*')->maxFiles(1),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('Imagen')->render(function ($producto) {
                $image = $producto->attachment('image')->first();
                $alt = e($producto->Nombre);

                $imageUrl = null;
                if ($image) {
                    if (is_object($image) && method_exists($image, 'url')) {
                        $imageUrl = $image->url();
                    }
                    if (empty($imageUrl)) {
                        $path = $image->path ?? null;
                        if ($path) {
                            $base = config('filesystems.disks.public.url', url('/storage'));
                            $imageUrl = rtrim($base, '/') . '/' . ltrim($path, '/');
                        }
                    }
                }

                if (!empty($imageUrl)) {
                    $escapedUrl = e($imageUrl);
                    $imgCard = '<img src="' . $escapedUrl . '" alt="' . $alt . '" class="product-card-img" style="width:100%;height:180px;object-fit:cover;display:block">';
                    $cardImageTag = '<button type="button" class="orchid-image-modal-trigger" data-orchid-image-src="' . $escapedUrl . '" data-orchid-image-alt="' . $alt . '" style="all:unset;display:block;cursor:pointer;width:100%">'
                        . $imgCard
                        . '</button>';

                    $tableImgTag = '<button type="button" class="orchid-image-modal-trigger" data-orchid-image-src="' . $escapedUrl . '" data-orchid-image-alt="' . $alt . '" style="all:unset;display:inline-block;cursor:pointer">'
                        . '<img src="' . $escapedUrl . '" alt="' . $alt . '" style="width:80px;height:50px;object-fit:cover;display:block;border-radius:4px">'
                        . '</button>';
                } else {
                    $svg = rawurlencode('<svg xmlns="http:\/\/www.w3.org\/2000\/svg" width="640" height="360"><rect fill="#f3f4f6" width="100%" height="100%"\/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-size="28" font-family="Arial, Helvetica, sans-serif">Sin imagen<\/text><\/svg>');
                    $src = 'data:image/svg+xml;utf8,' . $svg;
                    $cardImageTag = '<img src="' . $src . '" alt="Sin imagen" class="product-card-img no-image" style="width:100%;height:180px;object-fit:cover;display:block">';
                    $tableImgTag = '<img src="' . $src . '" alt="Sin imagen" style="width:80px;height:50px;object-fit:cover;display:block;border-radius:4px">';
                }

                $fields = [
                    'Nombre' => $producto->Nombre,
                    'Descripción' => $producto->Descripcion,
                    'Precio' => '$ ' . number_format((float)$producto->Precio, 2, '.', ','),
                    'm² por caja' => $producto->M2_Por_Caja,
                    'Piezas por caja' => $producto->Piezas_Por_Caja,
                    'Unidad de venta' => $producto->Unidad_Venta,
                    'Formato (cm)' => ($producto->Ancho_Pieza_Cm && $producto->Largo_Pieza_Cm)
                        ? ($producto->Ancho_Pieza_Cm . '×' . $producto->Largo_Pieza_Cm)
                        : null,
                    'Marca' => $producto->Marca,
                    'Modelo' => $producto->Modelo,
                    'Stock Mínimo' => $producto->Stock_Minimo,
                    'Categoría' => optional($producto->categoria)->Nombre,
                ];
                $infoHtml = '<div class="product-card-info" style="padding:10px 12px;display:flex;flex-direction:column;gap:4px">';
                foreach ($fields as $label => $value) {
                    if ($value === null || $value === '') continue;
                    $infoHtml .= '<div class="pci-row" style="display:flex;flex-direction:column"><span class="pci-label" style="font-size:11px;font-weight:600;letter-spacing:.5px;color:#6b7280;text-transform:uppercase">' . e($label) . '</span><span class="pci-value" style="font-size:14px;color:#111827">' . e($value) . '</span></div>';
                }
                $infoHtml .= '</div>';

                $base = '/admin/crud';
                $editUrl = $base . '/edit/producto-resources/' . $producto->id;
                $showUrl = $base . '/view/producto-resources/' . $producto->id;
                $actions = '<div class="product-card-actions" style="display:flex;gap:8px;padding:8px 12px 14px">'
                    . '<a href="' . e($showUrl) . '" class="btn btn-sm btn-outline-primary" style="flex:1;text-align:center">Ver</a>'
                    . '<a href="' . e($editUrl) . '" class="btn btn-sm btn-primary" style="flex:1;text-align:center">Editar</a>'
                    . '</div>';

                $cardHtml = '<div class="product-card" style="background:#fff;border:1px solid rgba(17,24,39,.06);border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(17,24,39,.06);display:flex;flex-direction:column">'
                    . $cardImageTag . $infoHtml . $actions . '</div>';

                return '<div class="product-table-img">' . $tableImgTag . '</div>' . $cardHtml;
            }),

            TD::make('id'),
            TD::make('Nombre', 'NOMBRE'),
            TD::make('Descripcion', 'DESCRIPCION'),
            TD::make('Precio', 'PRECIO'),
            TD::make('M2_Por_Caja', 'M²/CAJA'),
            TD::make('Piezas_Por_Caja', 'PIEZAS/CAJA'),
            TD::make('Unidad_Venta', 'UNIDAD VENTA'),
            TD::make('Formato', 'FORMATO (CM)')
                ->render(fn ($p) => ($p->Ancho_Pieza_Cm && $p->Largo_Pieza_Cm) ? ($p->Ancho_Pieza_Cm . '×' . $p->Largo_Pieza_Cm) : ''),
            TD::make('Marca', 'MARCA'),
            TD::make('Modelo', 'MODELO'),
            TD::make('Stock_Minimo', 'STOCK MINIMO'),
            TD::make('categoria.Nombre', 'CATEGORIA'),
            TD::make('created_at', 'Date of creation')->render(fn($m) => $m->created_at->toDateTimeString()),
            TD::make('updated_at', 'Update date')->render(fn($m) => $m->updated_at->toDateTimeString()),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('Nombre', 'NOMBRE'),
            Sight::make('Descripcion', 'DESCRIPCION'),
            Sight::make('Precio', 'PRECIO'),
            Sight::make('M2_Por_Caja', 'M²/CAJA'),
            Sight::make('Piezas_Por_Caja', 'PIEZAS/CAJA'),
            Sight::make('Unidad_Venta', 'UNIDAD VENTA'),
            Sight::make('Ancho_Pieza_Cm', 'ANCHO PIEZA (CM)'),
            Sight::make('Largo_Pieza_Cm', 'LARGO PIEZA (CM)'),
            Sight::make('Marca', 'MARCA'),
            Sight::make('Modelo', 'MODELO'),
            Sight::make('Stock_Minimo', 'STOCK MINIMO'),
            Sight::make('categoria.Nombre', 'CATEGORIA'),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function save(Request $request, Model $model): void
    {
        $model->fill($request->except('image'));
        $model->save();

        $imageIds = (array) $request->input('image', []);
        $imageIds = array_filter($imageIds, fn($v) => !empty($v));
        if (!empty($imageIds)) {
            $model->attachment()->sync($imageIds);
        }
    }

    public function with(): array
    {
        return ['categoria', 'attachment'];
    }
}
