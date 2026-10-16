<div class="card dash-card h-100">
    <div class="card-body">
        <div class="dash-card-head">
            <h4 class="dash-card-title"><i class="mdi mdi-bell-ring-outline"></i> Perlu Tindakan</h4>
        </div>
        @forelse ($attention as $item)
            @php($tag = $item['url'] ? 'a' : 'div')
            <{{ $tag }} @if ($item['url']) href="{{ $item['url'] }}" @endif class="dash-attention dash-tone-{{ $item['tone'] }}">
                <span class="dash-attention-icon"><i class="mdi {{ $item['icon'] }}"></i></span>
                <span class="flex-grow-1">
                    <span class="d-block fw-semibold">{{ $item['label'] }}</span>
                    <small class="text-muted">{{ $item['hint'] }}</small>
                </span>
                <span class="dash-attention-count">{{ \App\Support\DashboardMetrics::number($item['count']) }}</span>
            </{{ $tag }}>
        @empty
            <div class="dash-empty">
                <i class="mdi mdi-check-circle-outline text-success"></i>
                <span>Semua beres — tidak ada yang perlu ditindaklanjuti.</span>
            </div>
        @endforelse
    </div>
</div>
