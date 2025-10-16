<?php

namespace App\Orchid\Resources;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Crud\ResourceRequest;


class ProductoResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Producto::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('Nombre')
                ->title('Nombre')
                ->placeholder('Ingresa el nombre del producto'),
            Input::make('Descripcion')
                ->title('Descripcion')
                ->placeholder('Ingresa la descripcion del producto'),
            Input::make('Precio')
                ->type('number')
                ->title('Precio')
                ->step(0.01)
                ->placeholder('Ingresa el precio del producto'),
            Input::make('Marca')
                ->title('Marca')
                ->placeholder('Ingresa la marca del producto'),
            Input::make('Modelo')
                ->title('Modelo')
                ->placeholder('Ingresa el modelo del producto'),
            Input::make('Stock_Minimo')
                ->type('number')
                ->title('Stock Minimo')
                ->placeholder('Ingresa el stock mínimo'),

            // Selector para la categoría relacionada
            Select::make('categoria_id')
                ->title('Categoria')
                ->fromModel(Categoria::class, 'Nombre')
                ->empty('Selecciona una categoria'),

                // Subida de imagen con Attachments de Orchid (grupo 'image')
                Upload::make('image')
                ->title('Imagen')
                ->groups('image')
                ->acceptedFiles('image/*')
                ->maxFiles(1),
        ];
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id'),

            TD::make('Nombre', 'NOMBRE'),
            TD::make('Descripcion', 'DESCRIPCION'),
            TD::make('Precio', 'PRECIO'),
            TD::make('Marca', 'MARCA'),
            TD::make('Modelo', 'MODELO'),
            TD::make('Stock_Minimo', 'STOCK MINIMO'),

            TD::make('categoria.Nombre', 'CATEGORIA'),

            TD::make('Imagen')
            ->render(function ($producto) {
                $image = $producto->attachment('image')->first();
                if (!$image) {
                    return 'Sin Imagen';
                }
                $url = method_exists($image, 'url') ? $image->url() : null;
                if (empty($url)) {
                    $path = $image->path ?? null;
                    if ($path) {
                        // Build a public URL to the storage path
                        $base = config('filesystems.disks.public.url', url('/storage'));
                        $url = rtrim($base, '/') . '/' . ltrim($path, '/');
                    }
                }
                if (!$url) {
                    return 'Sin Imagen';
                }

                // Pure CSS :target modal to avoid Alpine dependencies
                static $cssInjected = false;
                $thumbStyles = 'object-fit:cover;border-radius:4px;cursor:pointer;';
                $escapedUrl = e($url);
                $alt = e($producto->Nombre);
                $id = 'img-modal-' . e($producto->id);

                $css = '';
                if (!$cssInjected) {
                    $css = '<style>
                    .modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.6);z-index:9999;padding:1rem}
                    .modal-overlay:target{display:flex}
                    .modal-card{position:relative;background:#fff;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,.2);padding:.5rem;max-width:90vw;max-height:90vh}
                    .modal-card img{display:block;max-height:80vh;width:auto}
                    .modal-close{position:absolute;top:.5rem;right:.75rem;font-size:1.5rem;color:#6b7280;text-decoration:none}
                    </style>';
                    $cssInjected = true;
                }

                return $css
                    . '<a href="#' . $id . '"><img src="' . $escapedUrl . '" alt="' . $alt . '" width="50" height="50" style="' . $thumbStyles . '"></a>'
                    . '<div id="' . $id . '" class="modal-overlay">'
                        . '<a href="#" class="modal-close" aria-label="Cerrar">&times;</a>'
                        . '<div class="modal-card">'
                            . '<img src="' . $escapedUrl . '" alt="' . $alt . '">' 
                        . '</div>'
                    . '</div>';
            }),

            TD::make('created_at', 'Date of creation')
                ->render(function ($model) {
                    return $model->created_at->toDateTimeString();
                }),

            TD::make('updated_at', 'Update date')
                ->render(function ($model) {
                    return $model->updated_at->toDateTimeString();
                }),
        ];
    }

    /**
     * Get the sights displayed by the resource.
     *
     * @return Sight[]
     */
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
            Sight::make('Imagen')->render(function ($producto) {
                $image = $producto->attachment('image')->first();
                if (!$image) {
                    return 'Sin Imagen';
                }
                $url = method_exists($image, 'url') ? $image->url() : null;
                if (empty($url)) {
                    $path = $image->path ?? null;
                    if ($path) {
                        $base = config('filesystems.disks.public.url', url('/storage'));
                        $url = rtrim($base, '/') . '/' . ltrim($path, '/');
                    }
                }
                if (!$url) {
                    return 'Sin Imagen';
                }

                // Pure CSS :target modal
                static $cssInjected2 = false;
                $escapedUrl = e($url);
                $alt = e($producto->Nombre);
                $id = 'img-modal-detail-' . e($producto->id);

                $css = '';
                if (!$cssInjected2) {
                    $css = '<style>
                    .modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.6);z-index:9999;padding:1rem}
                    .modal-overlay:target{display:flex}
                    .modal-card{position:relative;background:#fff;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,.2);padding:.5rem;max-width:90vw;max-height:90vh}
                    .modal-card img{display:block;max-height:80vh;width:auto}
                    .modal-close{position:absolute;top:.5rem;right:.75rem;font-size:1.5rem;color:#6b7280;text-decoration:none}
                    </style>';
                    $cssInjected2 = true;
                }

                return $css
                    . '<a href="#' . $id . '"><img src="' . $escapedUrl . '" alt="' . $alt . '" width="120" style="object-fit:contain;cursor:pointer"></a>'
                    . '<div id="' . $id . '" class="modal-overlay">'
                        . '<a href="#" class="modal-close" aria-label="Cerrar">&times;</a>'
                        . '<div class="modal-card">'
                            . '<img src="' . $escapedUrl . '" alt="' . $alt . '">' 
                        . '</div>'
                    . '</div>';
            }),
            Sight::make('created_at', 'Date of creation'),
            Sight::make('updated_at', 'Update date'),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }

    

    public function save(Request $request, Model $model): void
    {
        // Guarda los datos principales del producto
        $model->fill($request->except('image'));
        $model->save();

        // Asociar attachments subidos desde el campo Upload (recibe IDs)
        $imageIds = (array) $request->input('image', []);
        $imageIds = array_filter($imageIds, fn($v) => !empty($v));
        if (!empty($imageIds)) {
            // Reemplaza la imagen anterior por la nueva
            $model->attachment()->sync($imageIds);
        }
    }

    /**
     * Eager load relations for index.
     */
    public function with(): array
    {
    return ['categoria', 'attachment'];
    }
}
