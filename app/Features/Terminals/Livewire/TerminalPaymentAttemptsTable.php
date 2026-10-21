<?php

namespace App\Features\Terminals\Livewire;

use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\Tables\TerminalPaymentAttemptsTable as TableConfiguration;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TerminalPaymentAttemptsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function table(Table $table): Table
    {
        return TableConfiguration::configure($table)
            ->query(TerminalPaymentAttempt::query())
            ->queryStringIdentifier('terminal-payment-history')
            ->heading(__('terminals::messages.history.heading'))
            ->description(__('terminals::messages.history.description'));
    }

    #[On('terminal-payment-attempts-updated')]
    public function refreshTable(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('terminals::terminal-payment-attempts-table');
    }
}
