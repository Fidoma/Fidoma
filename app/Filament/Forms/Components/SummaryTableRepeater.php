<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Blade;

class SummaryTableRepeater extends Repeater
{
    /** @var array<string, string> | Closure */
    protected array|Closure $summaryRows = [];

    protected int|Closure $summaryTableColumnCount = 10;

    protected int|Closure $summaryLeadingColumnSpan = 6;

    /** @var array<string> */
    protected array $summaryActionNames = [];

    protected bool|Closure $hasSummaryActionsInTable = false;

    protected Component|Closure|null $summaryLeadingComponent = null;

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

    public function summaryActionsInTable(bool|Closure $condition = true): static
    {
        $this->hasSummaryActionsInTable = $condition;

        return $this;
    }

    public function summaryLeadingComponent(Component|Closure|null $component): static
    {
        $this->summaryLeadingComponent = $component;

        return $this;
    }

    protected function toTableEmbeddedHtml(): string
    {
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

        $actionsInTable = (bool) $this->evaluate($this->hasSummaryActionsInTable);
        $leadingComponent = $this->evaluate($this->summaryLeadingComponent);
        $leadingComponentHtml = '';

        if ($leadingComponent instanceof Component) {
            $leadingComponent = clone $leadingComponent;
            $leadingComponent->container($this->getContainer());
            $leadingComponentHtml = '<div class="fi-fo-summary-leading-component">'.$leadingComponent->toHtml().'</div>';
        }

        $summary = '<div class="fi-fo-summary-layout'.($actionsInTable ? ' fi-fo-summary-actions-in-table' : '').'">';
        $summary .= '<div class="fi-fo-summary-actions">'.$leadingComponentHtml.($actionsInTable ? '' : $inlineActions).'</div>';
        $summary .= '<div class="fi-fo-summary-table-wrapper">';
        $summary .= '<table class="fi-fo-summary-table" aria-label="Summenübersicht"><tbody>';

        $rowIndex = 0;

        foreach ($summaryRows as $label => $value) {
            $summary .= '<tr>';

            if ($actionsInTable && $rowIndex === 0) {
                $summary .= '<td class="fi-fo-summary-table-actions" rowspan="'.count($summaryRows).'">'.$inlineActions.'</td>';
            }

            $summary .= '<th scope="row">'.e($label).'</th>';
            $summary .= '<td>'.self::renderReadonlyInput($label, $value).'</td>';
            $summary .= '</tr>';
            $rowIndex++;
        }

        $summary .= '</tbody></table></div></div>';

        return substr_replace($html, $summary, $tableEndPosition + strlen('</table>'), 0);
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
