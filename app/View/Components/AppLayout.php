<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page de l'application : sidebar à gauche, titre de page, messages de confirmation.
 */
class AppLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
