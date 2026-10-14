<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Schemas\Components;

use Closure;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Blade;

class ReservationItemsRepeater extends Repeater
{
    /** @var array<string, string> | Closure */
    protected array|Closure $summaryRows = [];

    /** @param array<string, string> | Closure $rows */
    public function summaryRows(array|Closure $rows): static
    {
        $this->summaryRows = $rows;

        return $this;
    }

    protected function toTableEmbeddedHtml(): string
    {
        $hasReorderColumn = (count($this->getItems()) > 1)
            && ($this->isReorderableWithButtons() || $this->isReorderableWithDragAndDrop());
        $tableColumnCount = $hasReorderColumn ? 11 : 10;
        $leadingColumnSpan = $hasReorderColumn ? 7 : 6;
        $addAction = $this->getAction($this->getAddActionName());
        $inlineAddAction = ($this->isAddable() && $addAction?->isVisible())
            ? $addAction->toHtml()
            : '';
        $isAddable = $this->isAddable;
        $this->isAddable = false;

        try {
            $html = parent::toTableEmbeddedHtml();
        } finally {
            $this->isAddable = $isAddable;
        }

        $tableBodyEndPosition = strrpos($html, '</tbody>');

        if ($tableBodyEndPosition === false) {
            return $html;
        }

        $summaryRows = $this->evaluate($this->summaryRows);

        if (blank($summaryRows)) {
            return $html;
        }

        $summary = '<tr><td colspan="'.$tableColumnCount.'"></td></tr>';

        foreach ($summaryRows as $label => $value) {
            $summary .= '<tr style="border-width: 0">';
            $summary .= '<td colspan="'.$leadingColumnSpan.'" style="border-width: 0">'.$inlineAddAction.'</td>';
            $summary .= '<td colspan="2" style="border-width: 0">'.e($label).'</td>';
            $summary .= '<td style="border-width: 0">'.self::renderReadonlyInput($label, $value).'</td>';
            $summary .= '<td style="border-width: 0"></td>';
            $summary .= '</tr>';

            $inlineAddAction = '';
        }

        return substr_replace($html, $summary, $tableBodyEndPosition, 0);
    }

    private static function renderReadonlyInput(string $label, string $value): string
    {
        return Blade::render(
            <<<'BLADE'
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        :value="$value"
                        :aria-label="$label"
                        readonly
                    />
                </x-filament::input.wrapper>
            BLADE,
            compact('label', 'value'),
        );
    }
}
