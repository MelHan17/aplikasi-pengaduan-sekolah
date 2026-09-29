<?php

namespace App\Http\Controllers;

use App\Models\Aspiration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $isAdmin = $request->user()->role === 'admin';
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'student_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
            'category' => ['nullable', Rule::in(array_keys(config('aspirations.categories')))],
            'status' => ['nullable', Rule::in(array_keys(config('aspirations.statuses')))],
        ]);

        $query = Aspiration::query()->with(['user', 'updates.user'])->latest();

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (isset($filters['month'])) {
            [$year, $month] = explode('-', $filters['month']);
            $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        if (isset($filters['student_id']) && $isAdmin) {
            $query->where('user_id', $filters['student_id']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return view('dashboard.index', [
            'aspirations' => $query->paginate(8)->withQueryString(),
            'filters' => $filters,
            'isAdmin' => $isAdmin,
            'students' => $isAdmin ? User::where('role', 'student')->orderBy('name')->get() : collect(),
            'stats' => $isAdmin ? [
                'total' => Aspiration::count(),
                'open' => Aspiration::whereIn('status', ['submitted', 'reviewed', 'in_progress'])->count(),
                'resolved' => Aspiration::where('status', 'resolved')->count(),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'category' => ['required', Rule::in(array_keys(config('aspirations.categories')))],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $aspiration = $request->user()->aspirations()->create([
                ...$validated,
                'status' => 'submitted',
            ]);

            $aspiration->updates()->create([
                'status' => 'submitted',
                'progress' => 'Aspirasi diterima dan menunggu ditinjau oleh tim sekolah.',
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Aspirasi berhasil dikirim. Progresnya dapat dipantau di sini.');
    }

    public function update(Request $request, Aspiration $aspiration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('aspirations.statuses')))],
            'progress' => ['nullable', 'string', 'max:2000'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $aspiration, $validated): void {
            $aspiration->update(['status' => $validated['status']]);
            $aspiration->updates()->create([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Status dan catatan penyelesaian berhasil diperbarui.');
    }
}
