<?php

namespace App\Livewire;

use Livewire\Component;

class FloatingAiWidget extends Component
{
    public bool $isOpen = false;

    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function render()
    {
        return view('livewire.floating-ai-widget');
    }
}
