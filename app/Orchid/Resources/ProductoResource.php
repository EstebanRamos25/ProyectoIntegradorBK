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
            Input::make('Precio')->type('number')->title('Precio')->step(0.01)->placeholder('Ingresa el precio del producto'),
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

                static $cssInjected = false;
                $alt = e($producto->Nombre);
                $id = 'img-modal-' . e($producto->id);
                $css = '';
                if (!$cssInjected) {
                    $css = '<style>.modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.6);z-index:9999;padding:1rem}.modal-overlay:target{display:flex}.modal-card{position:relative;background:#fff;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,.2);padding:.5rem;max-width:90vw;max-height:90vh}.modal-card img{display:block;max-height:80vh;width:auto}.modal-close{position:absolute;top:.5rem;right:.75rem;font-size:1.5rem;color:#6b7280;text-decoration:none}</style>';
                    $cssInjected = true;
                }

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
                    $imgCard = '<img src="' . $escapedUrl . '" alt="' . $alt . '" class="product-card-img" style="width:100%;height:180px;object-fit:cover;display:block;cursor:pointer">';
                    $cardImageTag = '<a href="#' . $id . '">' . $imgCard . '</a>'
                        . '<div id="' . $id . '" class="modal-overlay">'
                            . '<a href="#" class="modal-close" aria-label="Cerrar">&times;</a>'
                            . '<div class="modal-card">'
                                . '<img src="' . $escapedUrl . '" alt="' . $alt . '">'
                            . '</div>'
                        . '</div>';
                    $tableImgTag = '<img src="' . $escapedUrl . '" alt="' . $alt . '" style="width:80px;height:50px;object-fit:cover;display:block;border-radius:4px">';
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

                return $css . '<div class="product-table-img">' . $tableImgTag . '</div>' . $cardHtml;
            }),

            TD::make('id'),
            TD::make('Nombre', 'NOMBRE'),
            TD::make('Descripcion', 'DESCRIPCION'),
            TD::make('Precio', 'PRECIO'),
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
