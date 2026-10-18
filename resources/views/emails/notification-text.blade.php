{{-- Versi teks polos dari emails/notification (multipart/alternative). --}}
{!! $heading !!}

@foreach ($paragraphs as $paragraph)
{!! $paragraph !!}

@endforeach
@if (filled($ticket))
Nomor Tiket: {!! $ticket !!}

@endif
@foreach ($details as $label => $value)
{!! $label !!}: {!! $value !!}
@endforeach
@if (! empty($details))

@endif
@if ($quote)
{!! $quote['label'] !!}:
{!! $quote['text'] !!}

@endif
@if (! empty($steps))
Cara melacak status:
@foreach ($steps as $step)
{{ $loop->iteration }}. {!! $step !!}
@endforeach

@endif
@if ($action)
{!! $action['label'] !!}: {!! $action['url'] !!}

@endif
@foreach ($signature as $line)
{!! $line !!}
@endforeach

--
Email ini dikirim otomatis oleh sistem {!! config('app.name', 'Marimoi') !!}.
{!! url('/') !!}
