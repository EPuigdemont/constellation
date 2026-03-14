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
class ConstellationView extends Component
{
    public string $filterType = 'all';

    public string $filterTag = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterMonth = '';

    public string $filterWeekday = '';

    public function getGraphData(): array
    {
        $user = Auth::user();
        $service = new ConstellationService();

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
            $nodeIds = collect($data['nodes'])->filter(fn ($n) => $n['month'] === $month)->pluck('id')->all();
            $data['nodes'] = array_values(array_filter($data['nodes'], fn ($n) => $n['month'] === $month));
            $data['edges'] = array_values(array_filter($data['edges'], fn ($e) => in_array($e['source'], $nodeIds) && in_array($e['target'], $nodeIds)));
        }

        if ($this->filterWeekday !== '') {
            $weekday = (int) $this->filterWeekday;
            $nodeIds = collect($data['nodes'])->filter(fn ($n) => $n['day_of_week'] === $weekday)->pluck('id')->all();
            $data['nodes'] = array_values(array_filter($data['nodes'], fn ($n) => $n['day_of_week'] === $weekday));
            $data['edges'] = array_values(array_filter($data['edges'], fn ($e) => in_array($e['source'], $nodeIds) && in_array($e['target'], $nodeIds)));
        }

        return $data;
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
