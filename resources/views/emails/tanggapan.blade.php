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
            <p>Terima kasih telah memberikan tanggapan di <strong>Marimoi</strong>. Masukan Anda sudah kami terima dan
                akan segera diproses.</p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'diproses')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>Tanggapan Anda saat ini sedang <strong>diproses</strong> oleh tim kami. Kami akan segera menghubungi Anda
                jika diperlukan informasi tambahan.</p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'selesai')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>Tanggapan Anda telah kami <strong>selesaikan</strong>. Terima kasih atas partisipasi Anda membantu kami
                meningkatkan layanan.</p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'ditolak')
            <h2>Halo {{ $data->nama ?? $data['nama'] }},</h2>
            <p>Mohon maaf, setelah kami tinjau, tanggapan Anda <strong>ditolak</strong> karena tidak memenuhi kriteria
                yang telah ditentukan.</p>
            <p>Jika ada pertanyaan lebih lanjut, silakan hubungi tim kami.</p>
            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'admin')
            <h2>Halo Admin,</h2>
            <p>Ada tanggapan baru dari pengguna melalui <strong>Marimoi</strong>:</p>
            <div class="admin-details">
                <ul>
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
