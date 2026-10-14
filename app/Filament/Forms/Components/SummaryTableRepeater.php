<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Blade;

class SummaryTableRepeater extends Repeater
{
    /** @var array<string, string> | Closure */
    protected array|Closure $summaryRows = [];

    protected int|Closure $summaryTableColumnCount = 10;

    protected int|Closure $summaryLeadingColumnSpan = 6;

    /** @var array<string> */
    protected array $summaryActionNames = [];

    /** @param array<string, string> | Closure $rows */
    public function summaryRows(array|Closure $rows): static
    {
        $this->summaryRows = $rows;

        return $this;
    }

    public function summaryLayout(int|Closure $tableColumnCount, int|Closure $leadingColumnSpan): static
    {
        $this->summaryTableColumnCount = $tableColumnCount;
        $this->summaryLeadingColumnSpan = $leadingColumnSpan;

        return $this;
    }

    /** @param array<Action> $actions */
    public function summaryActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->summaryActionNames[] = $action->getName();
        }

        return $this->registerActions($actions);
    }

    protected function toTableEmbeddedHtml(): string
    {
        $hasReorderColumn = (count($this->getItems()) > 1)
            && ($this->isReorderableWithButtons() || $this->isReorderableWithDragAndDrop());
        $tableColumnCount = $this->evaluate($this->summaryTableColumnCount) + ($hasReorderColumn ? 1 : 0);
        $leadingColumnSpan = $this->evaluate($this->summaryLeadingColumnSpan) + ($hasReorderColumn ? 1 : 0);
        $addAction = $this->getAction($this->getAddActionName());
        $inlineAddAction = ($this->isAddable() && $addAction?->isVisible())
            ? $addAction->toHtml()
            : '';
        $inlineActions = $inlineAddAction;

        foreach ($this->summaryActionNames as $actionName) {
            $action = $this->getAction($actionName);

            if ($action?->isVisible()) {
                $inlineActions .= $action->toHtml();
            }
        }

        if (filled($inlineActions)) {
            $inlineActions = '<div class="fi-fo-table-repeater-actions">'.$inlineActions.'</div>';
        }

        $isAddable = $this->isAddable;
        $this->isAddable = false;

        try {
            $html = parent::toTableEmbeddedHtml();
        } finally {
            $this->isAddable = $isAddable;
        }

        $tableEndPosition = strrpos($html, '</table>');

        if ($tableEndPosition === false) {
            return $html;
        }

        $summaryRows = $this->evaluate($this->summaryRows);

        if (blank($summaryRows)) {
            return $html;
        }

        $summary = '<table aria-label="Summenübersicht">';
        $summary .= $this->renderSummaryColgroup($hasReorderColumn, $tableColumnCount);
        $summary .= '<tbody>';

        foreach ($summaryRows as $label => $value) {
            $summary .= '<tr style="border-width: 0">';
            $summary .= '<td colspan="'.$leadingColumnSpan.'" style="border-width: 0">'.$inlineActions.'</td>';
            $summary .= '<td colspan="2" style="border-width: 0">'.e($label).'</td>';
            $summary .= '<td style="border-width: 0">'.self::renderReadonlyInput($label, $value).'</td>';
            $summary .= '<td style="border-width: 0"></td>';
            $summary .= '</tr>';

            $inlineActions = '';
        }

        $summary .= '</tbody></table>';

        return substr_replace($html, $summary, $tableEndPosition + strlen('</table>'), 0);
    }

    private function renderSummaryColgroup(bool $hasReorderColumn, int $tableColumnCount): string
    {
        $columns = $hasReorderColumn ? ['1%'] : [];

        foreach ($this->getTableColumns() ?? [] as $column) {
            $columns[] = $column->getWidth();
        }

        $columns = array_pad($columns, $tableColumnCount, null);
        $colgroup = '<colgroup>';

        foreach (array_slice($columns, 0, $tableColumnCount) as $width) {
            $colgroup .= filled($width)
                ? '<col style="width: '.e($width).'">'
                : '<col>';
        }

        return $colgroup.'</colgroup>';
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
