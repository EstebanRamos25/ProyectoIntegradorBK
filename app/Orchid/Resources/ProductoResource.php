<?php

namespace App\Orchid\Resources;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
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

                Picture::make('image')
                ->title('Imagen')
                ->storage('public') // Usa el disco correcto
                ->accepted(['image/*']) // Acepta solo imágenes
                ->maxFiles(1), // Permite solo una imagen
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
                // Obtén la URL de la imagen usando el sistema de attachments
                $image = $producto->attachment('image')->first();
                return $image
                    ? "<img src='{$image->url}' alt='{$producto->Nombre}' width='50' height='50'>"
                    : 'Sin Imagen';
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
                return $image
                    ? "<img src='{$image->url}' alt='{$producto->Nombre}' width='100'>"
                    : 'Sin Imagen';
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
    
        // Procesar el campo 'image' (que actualmente contiene una URL)
        $imageUrl = $request->input('image');
    
        if ($imageUrl) {
            // Busca el archivo adjunto en la tabla 'attachments' usando la URL
            $attachment = DB::table('attachments')->where('path', 'like', '%' . basename($imageUrl))->first();
    
            if ($attachment) {
                // Asocia el archivo adjunto al modelo
                $model->attachment()->syncWithoutDetaching([$attachment->id]);
            }
        }
    }
}
