<?php

namespace Tests\Unit;

use Filament\Forms\Components\Select;
use Tests\TestCase;

class SelectDefaultsTest extends TestCase
{
    public function test_regular_select_uses_the_first_option_without_a_placeholder(): void
    {
        $select = Select::make('status')->options([
            'draft' => 'Entwurf',
            'active' => 'Aktiv',
        ]);

        $this->assertFalse($select->canSelectPlaceholder());
        $this->assertSame('draft', $select->getDefaultState());
    }

    public function test_linked_select_keeps_its_placeholder_and_empty_default(): void
    {
        $select = Select::make('client_id')->options([
            10 => 'Erster Kunde',
            20 => 'Zweiter Kunde',
        ]);

        $this->assertTrue($select->canSelectPlaceholder());
        $this->assertNull($select->getDefaultState());
    }

    public function test_table_filter_select_keeps_its_empty_default(): void
    {
        $select = Select::make('value')->options([
            'unassigned' => 'Nicht zugeordnet',
            'assigned' => 'Zugeordnet',
        ]);

        $this->assertTrue($select->canSelectPlaceholder());
        $this->assertNull($select->getDefaultState());
    }
}
