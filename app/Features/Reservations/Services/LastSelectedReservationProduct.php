<?php

namespace App\Features\Reservations\Services;

use App\Features\Products\Models\Product;

final class LastSelectedReservationProduct
{
    public function remember(mixed $productId): void
    {
        if (! is_numeric($productId)) {
            return;
        }

        if (Product::query()->whereKey((int) $productId)->exists()) {
            session()->put($this->sessionKey(), (int) $productId);
        }
    }

    public function currentId(): ?int
    {
        $productId = session()->get($this->sessionKey());

        if (! is_numeric($productId)) {
            return null;
        }

        return Product::query()->whereKey((int) $productId)->exists()
            ? (int) $productId
            : null;
    }

    private function sessionKey(): string
    {
        return 'reservations.last_selected_product.'.(auth()->id() ?? 'guest');
    }
}
