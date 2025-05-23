<?php

namespace App\View\Components\Frontend;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusIcon extends Component
{
    /**
     * The status type (completed, failed, pending).
     */
    public string $status;

    /**
     * The desired width of the icon.
     */
    public string|int $width;

    /**
     * The desired height of the icon.
     */
    public string|int $height;

    /**
     * The color for the icon elements (stroke or fill).
     * Defaults to 'currentColor' for better theme integration,
     * except for pending which often has a specific loading color.
     */
    public string $color;

    /**
     * Calculated color based on status if default is used.
     */
    public string $finalColor;

    /**
     * Additional CSS classes.
     */
    public string $class;

    /**
     * Create a new component instance.
     *
     * @param  string  $status  The status (completed, failed, pending)
     * @param  string|int  $width  Default '24'
     * @param  string|int  $height  Default '24'
     * @param  string  $color  Default depends on status ('currentColor' or '#9EA7AB')
     * @param  string  $class  Additional CSS classes
     * @return void
     */
    public function __construct(
        string $status,
        string|int $width = 24,
        string|int $height = 24,
        string $color = '',
        string $class = ''
    ) {
        $this->status = strtolower($status);
        $this->width = $width;
        $this->height = $height;
        $this->class = $class;
        $this->color = $color;

        if (empty($color)) {
            match ($this->status) {
                'pending' => $this->finalColor = '#7A8488',
                'failed' => $this->finalColor = 'red',
                'completed' => $this->finalColor = 'green',
                default => $this->finalColor = 'currentColor',
            };
        } else {
            $this->finalColor = $color;
        }
        if (empty($class)) {
            $this->class = 'btn-icon';
            match ($this->status) {
                'pending' => $this->class .= ' ',
                'failed' => $this->class .= ' text-danger',
                'completed' => $this->class .= ' text-success',
                default => $this->class .= ' text-secondary',
            };
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render(): View
    {
        return view('components.frontend.status-icon');
    }
}
