<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request): View|RedirectResponse
    {
        $input = $request->validated();
        $filters = [
            'q' => $input['q'] ?? '',
            'segment' => $input['segment'] ?? '',
            'status' => $input['status'] ?? '',
            'source' => $input['source'] ?? '',
            'sort' => $input['sort'] ?? 'newest',
        ];

        // Overview counts are global; filters only affect the lead list below.
        $segmentCounts = Lead::query()->select('segment')
            ->selectRaw('COUNT(*) as total')->groupBy('segment')->pluck('total', 'segment');
        $stats = [
            'total' => (int) $segmentCounts->sum(),
            'HOT' => (int) ($segmentCounts['HOT'] ?? 0),
            'WARM' => (int) ($segmentCounts['WARM'] ?? 0),
            'COLD' => (int) ($segmentCounts['COLD'] ?? 0),
        ];
        $statusCounts = Lead::query()->select('status')
            ->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $statusStats = [];
        foreach (Lead::STATUS_LABELS as $status => $label) {
            $statusStats[$status] = (int) ($statusCounts[$status] ?? 0);
        }

        $query = Lead::query();

        if ($filters['q'] !== '') {
            // Treat %, _ and ! as literal search text, with bound parameters.
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$search])
                    ->orWhereRaw("contact LIKE ? ESCAPE '!'", [$search])
                    ->orWhereRaw("location LIKE ? ESCAPE '!'", [$search]);
            });
        }

        foreach (['segment', 'status', 'source'] as $field) {
            if ($filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if ($filters['sort'] === 'score_desc') {
            $query->orderByDesc('score');
        }

        $leads = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(10)->appends($filters);

        // Editing can move the last result off the current page; preserve the filters.
        if ($leads->currentPage() > $leads->lastPage()) {
            return to_route('dashboard', array_merge($filters, ['page' => $leads->lastPage()]));
        }
        $back = array_filter(array_merge($filters, ['page' => $leads->currentPage()]), fn ($value) => $value !== '');

        $sources = Lead::query()->whereNotNull('source')->where('source', '!=', '')
            ->distinct()->orderBy('source')->pluck('source');

        return view('dashboard', compact('filters', 'stats', 'statusStats', 'leads', 'sources', 'back'));
    }
}
