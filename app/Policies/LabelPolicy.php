<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;

class LabelPolicy
{
    public function __construct(private BoardPolicy $boardPolicy) {}

    /**
     * Any board member can edit the board's labels.
     */
    public function update(User $user, Label $label): bool
    {
        return $label->board !== null && $this->boardPolicy->update($user, $label->board);
    }

    /**
     * Any board member can delete the board's labels.
     */
    public function delete(User $user, Label $label): bool
    {
        return $this->update($user, $label);
    }
}
