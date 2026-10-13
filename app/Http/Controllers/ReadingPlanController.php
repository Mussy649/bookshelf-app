<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * Display the authenticated user's reading plans.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $currentStatus = (string) $request->query('status', '');
        $status = $currentStatus !== ''
            ? ReadingPlanStatus::tryFrom($currentStatus)
            : null;

        if ($currentStatus !== '' && $status === null) {
            $currentStatus = '';
        }

        $query = $user->readingPlans()
            ->with('book')
            ->orderBy('target_date');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        $readingPlans = $query->get();

        return view(
            'reading-plans.index',
            compact('readingPlans', 'currentStatus')
        );
    }

    /**
     * Show the form for creating a reading plan.
     */
    public function create(): View
    {
        $books = Book::query()
            ->orderBy('title')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * Store a newly created reading plan.
     */
    public function store(
        StoreReadingPlanRequest $request
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $user->readingPlans()->create([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * Show the form for editing a reading plan.
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load('book');

        $readingPlan = $plan;

        return view(
            'reading-plans.edit',
            compact('readingPlan')
        );
    }

    /**
     * Update the reading plan.
     */
    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $plan
    ): RedirectResponse {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * Mark the reading plan as completed.
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読了しました。');
    }

    /**
     * Delete the reading plan.
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
