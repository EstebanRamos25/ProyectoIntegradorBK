<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ThreeQuote;
use App\Models\User;

class ThreeQuotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ThreeQuote $quote): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ThreeQuote $quote): bool
    {
        return false;
    }

    public function delete(User $user, ThreeQuote $quote): bool
    {
        return false;
    }

    public function restore(User $user, ThreeQuote $quote): bool
    {
        return false;
    }

    public function forceDelete(User $user, ThreeQuote $quote): bool
    {
        return false;
    }
}
