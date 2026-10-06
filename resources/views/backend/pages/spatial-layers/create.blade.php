@extends('backend.partials.main', ['title' => 'Tambah Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers-plus"></i></span>
            Tambah Layer
        </h3>
        <a href="{{ route('spatial-layers.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        @foreach (['Informasi Layer' => 1, 'Impor Data' => 2, 'Mapping Atribut' => 3, 'Review & Publish' => 4] as $label => $n)
                            <div class="step {{ $currentStep == $n ? 'active' : ($wizardStep !== null && $n < $wizardStep ? 'completed' : '') }}"
                                title="{{ $label }}">{{ $n }}</div>
                            @if ($n < 4)
                                <div class="step-connector {{ $wizardStep !== null && $n < $wizardStep ? 'completed' : '' }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="text-center text-muted small mb-4">
                        Tahap {{ $currentStep }}/4:
                        {{ ['Informasi Layer', 'Impor Data', 'Mapping Atribut', 'Review & Publish'][$currentStep - 1] }}
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @switch($currentStep)
                        @case(1)
                            @include('backend.pages.spatial-layers._wizard-step-1-info')
                        @break

                        @case(2)
                            @include('backend.pages.spatial-layers._wizard-step-2-import')
                        @break

                        @case(3)
                            @include('backend.pages.spatial-layers._wizard-step-3-mapping')
                        @break

                        @default
                            @include('backend.pages.spatial-layers._wizard-step-4-review')
                    @endswitch
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
            align-items: center;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dee2e6, #adb5bd);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #6c757d;
            font-weight: bold;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step.active {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        .step.completed {
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
            box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
        }

        .step-connector {
            width: 60px;
            height: 2px;
            background-color: #dee2e6;
        }

        .step-connector.completed {
            background: linear-gradient(90deg, #198754, #20c997);
        }
    </style>
@endpush
