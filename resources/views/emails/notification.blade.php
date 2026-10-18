{{-- Template HTML bersama untuk AspirasiMail & TanggapanMail. Isi disusun di mailable (method message()). --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name', 'Marimoi') }}</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f5f8; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f5f8;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:600px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:8px;">
                    <tr>
                        <td style="background-color:#0f4c81; padding:16px 24px; border-radius:8px 8px 0 0;">
                            {{-- Logo disematkan (CID) agar tampil tanpa bergantung pada APP_URL / gambar eksternal. --}}
                            @php($logoPath = public_path('frontend/img/logo/logo-white-email.png'))
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="middle" style="padding-right:12px;">
                                        <img src="{{ isset($message) ? $message->embed($logoPath) : asset('frontend/img/logo/logo-white-email.png') }}"
                                            width="48" height="45" alt="Logo MARIMOI"
                                            style="display:block; width:48px; height:45px; border:0; outline:none; text-decoration:none;">
                                    </td>
                                    <td valign="middle">
                                        <span style="display:block; font-size:20px; font-weight:bold; color:#ffffff; letter-spacing:1px; line-height:1.2;">MARIMOI</span>
                                        <span style="display:block; font-size:12px; color:#cfe0f0; line-height:1.4;">Bappeda Maluku Utara</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px; font-size:15px; line-height:1.6;">
                            <p style="margin:0 0 16px; font-size:17px; font-weight:bold;">{{ $heading }}</p>

                            @foreach ($paragraphs as $paragraph)
                                <p style="margin:0 0 14px;">{{ $paragraph }}</p>
                            @endforeach

                            @if (filled($ticket))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;">
                                    <tr>
                                        <td align="center" style="background-color:#eef5fb; border:1px solid #9cc3e6; border-radius:6px; padding:12px;">
                                            <span style="display:block; font-size:13px; color:#4b5563;">Nomor Tiket</span>
                                            <span style="font-family:'Courier New', monospace; font-size:18px; font-weight:bold; color:#0f4c81; letter-spacing:1px;">{{ $ticket }}</span>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if (! empty($details))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    style="margin:0 0 18px; border-collapse:collapse; font-size:14px;">
                                    @foreach ($details as $label => $value)
                                        <tr>
                                            <td valign="top" style="padding:7px 10px; width:38%; background-color:#f9fafb; border:1px solid #e5e7eb; color:#4b5563;">{{ $label }}</td>
                                            <td valign="top" style="padding:7px 10px; border:1px solid #e5e7eb; word-break:break-word;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if ($quote)
                                <p style="margin:0 0 6px; font-weight:bold;">{{ $quote['label'] }}</p>
                                <div style="margin:0 0 18px; padding:12px 14px; background-color:#f9fafb; border-left:4px solid #0f4c81; white-space:pre-line; word-break:break-word;">{{ $quote['text'] }}</div>
                            @endif

                            @if (! empty($steps))
                                <p style="margin:0 0 6px; font-weight:bold;">Cara melacak status</p>
                                <ol style="margin:0 0 18px; padding-left:20px;">
                                    @foreach ($steps as $step)
                                        <li style="margin:0 0 4px;">{{ $step }}</li>
                                    @endforeach
                                </ol>
                            @endif

                            @if ($action)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:6px 0 20px;">
                                    <tr>
                                        <td style="background-color:#0f4c81; border-radius:6px;">
                                            <a href="{{ $action['url'] }}" target="_blank" rel="noopener"
                                                style="display:inline-block; padding:11px 22px; color:#ffffff; font-weight:bold; text-decoration:none;">{{ $action['label'] }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:18px 0 0; padding-top:14px; border-top:1px solid #e5e7eb;">
                                @foreach ($signature as $line)
                                    {{ $line }}@if (! $loop->last)<br>@endif
                                @endforeach
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="max-width:600px; margin:14px auto 0; font-size:12px; line-height:1.5; color:#6b7280; text-align:center;">
                    Email ini dikirim otomatis oleh sistem {{ config('app.name', 'Marimoi') }}.<br>
                    <a href="{{ url('/') }}" style="color:#6b7280;">{{ preg_replace('#^https?://#', '', url('/')) }}</a>
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
