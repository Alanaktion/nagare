<?php

namespace App\Http\Controllers;

use App\Actions\Labels\CreateLabel;
use App\Actions\Labels\DeleteLabel;
use App\Actions\Labels\UpdateLabel;
use App\Http\Requests\Labels\StoreLabelRequest;
use App\Http\Requests\Labels\UpdateLabelRequest;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LabelController extends Controller
{
    public function store(StoreLabelRequest $request, Board $board, CreateLabel $createLabel): RedirectResponse
    {
        $createLabel->handle($board, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Label added.')]);

        return back();
    }

    public function update(UpdateLabelRequest $request, Label $label, UpdateLabel $updateLabel): RedirectResponse
    {
        $updateLabel->handle($label, $request->validated());

        return back();
    }

    public function destroy(Label $label, DeleteLabel $deleteLabel): RedirectResponse
    {
        Gate::authorize('delete', $label);

        $deleteLabel->handle($label);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Label deleted.')]);

        return back();
    }
}
