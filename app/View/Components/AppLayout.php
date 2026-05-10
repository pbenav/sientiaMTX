<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public $maxWidth;

    public function __construct($maxWidth = 'max-w-none')
    {
        $this->maxWidth = $maxWidth;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        if (request()->query('embed')) {
            return view('layouts.embed');
        }
        return view('layouts.app');
    }
}
