<?php

namespace App\Orchid\Resources;

use App\Models\ThreeQuote;
use App\Orchid\Actions\ConvertThreeQuoteToSaleAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class ThreeQuoteResource extends Resource
{
    public static $model = ThreeQuote::class;

    public static function label(): string
    {
        return 'Cotizaciones 3D';
    }

    public static function singularLabel(): string
    {
        return 'Cotización 3D';
    }

    public static function description(): ?string
    {
        return 'Cotizaciones enviadas por clientes desde Experiencia 3D.';
    }

    public function modelQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->newQuery()
            ->whereIn('status', ['sent', 'sold'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function with(): array
    {
        return ['scene', 'user', 'producto'];
    }

    public function fields(): array
    {
        return [
            Input::make('id')->title('ID')->readonly(),
            Input::make('status')->title('Estado')->readonly(),
            Input::make('scene.name')->title('Escena')->readonly(),
            Input::make('user.name')->title('Cliente')->readonly(),
            Input::make('producto.Nombre')->title('Producto')->readonly(),
            Input::make('boxes_required')->title('Cajas requeridas')->readonly(),
            Input::make('area_m2')->title('Área (m²)')->readonly(),
            Input::make('total')->title('Total')->readonly(),
            Input::make('sent_at')->title('Enviada')->readonly(),
            Input::make('sold_at')->title('Vendida')->readonly(),
            Input::make('venta_id')->title('Venta ID')->readonly(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('id', 'ID'),
            TD::make('status', 'ESTADO'),
            TD::make('scene.name', 'ESCENA'),
            TD::make('user.name', 'CLIENTE'),
            TD::make('producto.Nombre', 'PRODUCTO'),
            TD::make('boxes_required', 'CAJAS'),
            TD::make('total', 'TOTAL'),
            TD::make('convert', 'VENTA')->alignRight()->render(function (ThreeQuote $quote) {
                return Button::make('Convertir a venta')
                    ->icon('bs.cart-check')
                    ->method('action', [
                        '_action' => ConvertThreeQuoteToSaleAction::name(),
                        '_models' => [(int) $quote->id],
                    ])
                    ->canSee($quote->status === 'sent');
            }),
            TD::make('pdf_path', 'PDF')->render(function (ThreeQuote $quote) {
                if (!$quote->pdf_path) {
                    return '-';
                }

                return Link::make('PDF')
                    ->href(asset('storage/'.$quote->pdf_path))
                    ->target('_blank');
            }),
            TD::make('sent_at', 'ENVIADA'),
            TD::make('sold_at', 'VENDIDA'),
            TD::make('updated_at', 'ACTUALIZADO'),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('status', 'ESTADO'),
            Sight::make('scene.name', 'ESCENA'),
            Sight::make('user.name', 'CLIENTE'),
            Sight::make('producto.Nombre', 'PRODUCTO'),
            Sight::make('boxes_required', 'CAJAS REQUERIDAS'),
            Sight::make('area_m2', 'ÁREA (m²)'),
            Sight::make('total', 'TOTAL'),
            Sight::make('pdf_path', 'PDF PATH'),
            Sight::make('sent_at', 'ENVIADA'),
            Sight::make('sold_at', 'VENDIDA'),
            Sight::make('venta_id', 'VENTA ID'),
            Sight::make('created_at', 'CREADA'),
            Sight::make('updated_at', 'ACTUALIZADA'),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [
            new ConvertThreeQuoteToSaleAction(),
        ];
    }
}
