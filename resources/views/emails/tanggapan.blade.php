<!-- resources/views/emails/tanggapan.blade.php -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $type === 'admin' ? 'Notifikasi Admin Marimoi' : 'Marimoi' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .admin-details {
            background-color: #f1f3f4;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .kode-container {
            background-color: #e8f4f8;
            border: 1px solid #3498db;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }

        .kode-text {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 1px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        strong {
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="content">
        @if ($type === 'penerimaan')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>
                Terima kasih telah memberikan tanggapan di <strong>Marimoi</strong>. Masukan Anda sudah kami terima dan
                akan segera diproses.
            </p>

            @if (isset($data['kode']))
                <div class="kode-container">
                    <p style="margin: 0; font-size: 14px;">Nomor Tiket Anda:</p>
                    <span class="kode-text">{{ $data['kode'] }}</span>
                </div>
            @endif

            <p>
                <strong>Tanggapan Admin:</strong><br>
                {{ $data['response_admin'] ?? ($data['tanggapan_admin'] ?? '-') }}
            </p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'diproses')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>
                Tanggapan Anda saat ini sedang <strong>diproses</strong> oleh tim kami. Kami akan segera menghubungi
                Anda jika diperlukan informasi tambahan.
            </p>

            @if (isset($data['kode']))
                <div class="kode-container">
                    <p style="margin: 0; font-size: 14px;">Nomor Tiket:</p>
                    <span class="kode-text">{{ $data['kode'] }}</span>
                </div>
            @endif

            <p>
                <strong>Tanggapan Admin:</strong><br>
                {{ $data['response_admin'] ?? ($data['tanggapan_admin'] ?? '-') }}
            </p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'selesai')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>
                Tanggapan Anda telah kami <strong>selesaikan</strong>. Terima kasih atas partisipasi Anda membantu kami
                meningkatkan layanan.
            </p>

            @if (isset($data['kode']))
                <div class="kode-container">
                    <p style="margin: 0; font-size: 14px;">Nomor Tiket:</p>
                    <span class="kode-text">{{ $data['kode'] }}</span>
                </div>
            @endif

            <p>
                <strong>Tanggapan Admin:</strong><br>
                {{ $data['response_admin'] ?? ($data['tanggapan_admin'] ?? '-') }}
            </p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'ditolak')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>
                Mohon maaf, setelah kami tinjau, tanggapan Anda <strong>ditolak</strong> karena tidak memenuhi kriteria
                yang telah ditentukan.
            </p>

            @if (isset($data['kode']))
                <div class="kode-container">
                    <p style="margin: 0; font-size: 14px;">Nomor Tiket:</p>
                    <span class="kode-text">{{ $data['kode'] }}</span>
                </div>
            @endif

            <p>
                <strong>Tanggapan Admin:</strong><br>
                {{ $data['response_admin'] ?? ($data['tanggapan_admin'] ?? '-') }}
            </p>
            <p>Jika ada pertanyaan lebih lanjut, silakan hubungi tim kami.</p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'admin')
            <h2>Halo Admin,</h2>
            <p>Ada tanggapan baru dari pengguna melalui <strong>Marimoi</strong>:</p>
            <div class="admin-details">
                <ul>
                    @if (isset($data['kode']))
                        <li><strong>Kode Tiket:</strong> <span
                                style="font-family: monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">{{ $data['kode'] }}</span>
                        </li>
                    @endif
                    <li><strong>Nama Pengguna:</strong> {{ $data->nama ?? $data['nama'] }}</li>
                    <li><strong>Email:</strong> {{ $data->email ?? $data['email'] }}</li>
                    <li><strong>Tanggal/Waktu:</strong>
                        @if (is_object($data) && isset($data->created_at))
                            {{ $data->created_at->format('d/m/Y H:i:s') }}
                        @else
                            {{ date('d/m/Y H:i:s') }}
                        @endif
                    </li>
                    <li><strong>Isi Tanggapan:</strong><br>
                        <em>"{{ $data->tanggapan ?? $data['tanggapan'] }}"</em>
                    </li>
                </ul>
            </div>
            <p>Mohon ditindaklanjuti sesuai prosedur.</p>
            <div class="footer">
                <p><strong>Sistem Notifikasi Marimoi</strong></p>
            </div>
        @endif
    </div>
</body>

</html>
