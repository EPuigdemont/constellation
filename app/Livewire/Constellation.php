<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tag;
use App\Services\ConstellationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Constellation')]
class Constellation extends Component
{
    public string $filterType = 'all';

    public string $filterTag = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterMonth = '';

    public string $filterWeekday = '';

    /**
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    public function getGraphData(): array
    {
        $user = Auth::user();
        $service = new ConstellationService();

        /** @var array<string, string> $filters */
        $filters = ['type' => $this->filterType];

        if ($this->filterTag !== '') {
            $filters['tag'] = $this->filterTag;
        }
        if ($this->filterDateFrom !== '') {
            $filters['date_from'] = $this->filterDateFrom;
        }
        if ($this->filterDateTo !== '') {
            $filters['date_to'] = $this->filterDateTo;
        }

        $data = $service->buildGraph($user, $filters);

        // Apply client-presentable month/weekday post-filters
        if ($this->filterMonth !== '') {
            $month = (int) $this->filterMonth;
            /** @var list<string> $nodeIds */
            $nodeIds = collect($data['nodes'])->filter(fn (array $n): bool => (int) ($n['month'] ?? 0) === $month)->pluck('id')->all();
            /** @var list<array<string, mixed>> $filteredNodes */
            $filteredNodes = array_values(array_filter($data['nodes'], fn (array $n): bool => (int) ($n['month'] ?? 0) === $month));
            $data['nodes'] = $filteredNodes;
            /** @var list<array<string, mixed>> $filteredEdges */
            $filteredEdges = array_values(array_filter($data['edges'], fn (array $e): bool => in_array((string) ($e['source'] ?? ''), $nodeIds, true) && in_array((string) ($e['target'] ?? ''), $nodeIds, true)));
            $data['edges'] = $filteredEdges;
        }

        if ($this->filterWeekday !== '') {
            $weekday = (int) $this->filterWeekday;
            /** @var list<string> $nodeIds */
            $nodeIds = collect($data['nodes'])->filter(fn (array $n): bool => (int) ($n['day_of_week'] ?? 0) === $weekday)->pluck('id')->all();
            /** @var list<array<string, mixed>> $filteredNodes */
            $filteredNodes = array_values(array_filter($data['nodes'], fn (array $n): bool => (int) ($n['day_of_week'] ?? 0) === $weekday));
            $data['nodes'] = $filteredNodes;
            /** @var list<array<string, mixed>> $filteredEdges */
            $filteredEdges = array_values(array_filter($data['edges'], fn (array $e): bool => in_array((string) ($e['source'] ?? ''), $nodeIds, true) && in_array((string) ($e['target'] ?? ''), $nodeIds, true)));
            $data['edges'] = $filteredEdges;
        }

        return [
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
        ];
    }

    public function render(): View
    {
        $userTags = Tag::forUser(Auth::id())->orderBy('name')->get();
        $graphData = $this->getGraphData();

        return view('livewire.constellation-view', [
            'userTags' => $userTags,
            'graphData' => $graphData,
        ]);
    }
}
