<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public $maxWidth;

    public function __construct($maxWidth = 'max-w-[1600px]')
    {
        $this->maxWidth = $maxWidth;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
