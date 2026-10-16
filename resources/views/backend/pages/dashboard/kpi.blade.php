{{-- Kartu KPI: $label, $value, $icon, $tone (primary|success|warning|danger|info), opsional $note, $trend (float|null), $url. --}}
@php($tag = ! empty($url) ? 'a' : 'div')
<{{ $tag }} @if (! empty($url)) href="{{ $url }}" @endif class="dash-kpi dash-tone-{{ $tone ?? 'primary' }}">
    <span class="dash-kpi-icon"><i class="mdi {{ $icon }}"></i></span>
    <span class="dash-kpi-body">
        <span class="dash-kpi-label">{{ $label }}</span>
        <span class="dash-kpi-value">{{ $value }}</span>
        @if (! empty($note) || isset($trend))
            <span class="dash-kpi-note">
                @isset($trend)
                    <span class="dash-trend {{ $trend >= 0 ? 'is-up' : 'is-down' }}">
                        <i class="mdi {{ $trend >= 0 ? 'mdi-arrow-up' : 'mdi-arrow-down' }}"></i>{{ \App\Support\DashboardMetrics::number(abs($trend), 1) }}%
                    </span>
                @endisset
                {{ $note ?? '' }}
            </span>
        @endif
    </span>
</{{ $tag }}>
