<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\View;

class ProductGalleryLayout extends View
{
    protected $target = 'featuredProducts';

    protected $view = 'orchid.dashboard.product-gallery';

    public function __construct(string $target = 'featuredProducts')
    {
        $this->target = $target;
        parent::__construct($this->view);
    }
}
