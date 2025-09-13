<!-- resources/views/emails/aspirasi_mail.blade.php -->
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
            <h2>Halo {{ $data['nama_pengirim'] ?? 'Pengirim' }},</h2>
            <p>Terima kasih telah mengirimkan aspirasi melalui <strong>Marimoi</strong>. Aspirasi Anda telah kami terima
                dan akan kami tinjau.</p>

            <p><strong>Ringkasan Aspirasi:</strong></p>
            <ul>
                <li><strong>Jenis:</strong> {{ $data['jenis_aspirasi'] ?? '-' }}</li>
                <li><strong>Judul:</strong> {{ $data['judul_aspirasi'] ?? '-' }}</li>
            </ul>

            <div class="footer">
                <p>Salam hangat,<br><strong>Tim Marimoi</strong></p>
            </div>
        @elseif($type === 'admin')
            <h2>Halo Admin,</h2>
            <p>Ada aspirasi baru yang masuk melalui <strong>Marimoi</strong>:</p>

            <div class="admin-details">
                <ul>
                    <li><strong>Nama Pengirim:</strong> {{ $data['nama_pengirim'] ?? '-' }}</li>
                    <li><strong>Email:</strong> {{ $data['email'] ?? '-' }}</li>
                    <li><strong>Jenis Aspirasi:</strong> {{ $data['jenis_aspirasi'] ?? '-' }}</li>
                    <li><strong>Judul:</strong> {{ $data['judul_aspirasi'] ?? '-' }}</li>
                    <li><strong>Isi Aspirasi:</strong><br>
                        <em>"{{ $data['isi_aspirasi'] ?? '-' }}"</em>
                    </li>
                    @if (($data['jenis_aspirasi'] ?? '') == 'usulan')
                        <li><strong>Kategori Usulan:</strong> {{ $data['kategori_aspirasi'] ?? '-' }}</li>
                        <li><strong>OPD Penanggung Jawab:</strong> {{ $data['opd_terkait'] ?? '-' }}</li>
                    @endif
                    @if (!empty($data['latitude'] ?? '') && !empty($data['longitude'] ?? ''))
                        <li><strong>Koordinat:</strong> {{ $data['latitude'] ?? '-' }}, {{ $data['longitude'] ?? '-' }}</li>
                    @endif
                    @if (!empty($data['lampiran'] ?? ''))
                        <li><strong>Lampiran:</strong> {{ $data['lampiran'] ?? '-' }}</li>
                    @endif
                </ul>
            </div>

            <p>Mohon ditindaklanjuti sesuai prosedur.</p>
            <div class="footer">
                <p><strong>Sistem Notifikasi Marimoi</strong></p>
            </div>
        @elseif($type === 'opd')
            <h2>Halo Tim OPD,</h2>
            <p>Ada aspirasi baru yang perlu ditindaklanjuti oleh OPD Anda melalui <strong>Marimoi</strong>:</p>

            <div class="admin-details">
                <ul>
                    <li><strong>Nama Pengirim:</strong> {{ $data['nama_pengirim'] ?? '-' }}</li>
                    <li><strong>Email:</strong> {{ $data['email'] ?? '-' }}</li>
                    <li><strong>No. Telepon:</strong> {{ $data['phone'] ?? '-' }}</li>
                    <li><strong>Alamat:</strong> {{ $data['alamat'] ?? '-' }}</li>
                    <li><strong>Jenis Aspirasi:</strong> {{ $data['jenis_aspirasi'] ?? '-' }}</li>
                    <li><strong>Judul:</strong> {{ $data['judul_aspirasi'] ?? '-' }}</li>
                    <li><strong>Isi Aspirasi:</strong><br>
                        <em>"{{ $data['isi_aspirasi'] ?? '-' }}"</em>
                    </li>
                    @if (($data['jenis_aspirasi'] ?? '') == 'usulan')
                        <li><strong>Kategori Usulan:</strong> {{ $data['kategori_aspirasi'] ?? '-' }}</li>
                    @endif
                    @if (!empty($data['latitude'] ?? '') && !empty($data['longitude'] ?? ''))
                        <li><strong>Koordinat:</strong> {{ $data['latitude'] ?? '-' }}, {{ $data['longitude'] ?? '-' }}</li>
                    @endif
                    @if (!empty($data['lampiran'] ?? ''))
                        <li><strong>Lampiran:</strong> {{ $data['lampiran'] ?? '-' }}</li>
                    @endif
                    @if (!empty($data['tanggal'] ?? ''))
                        <li><strong>Tanggal Diterima:</strong> {{ $data['tanggal'] ?? '-' }}</li>
                    @endif
                </ul>
            </div>

            <p>Silakan login ke sistem untuk memberikan tanggapan atau tindak lanjut yang diperlukan.</p>
            <div class="footer">
                <p>Terima kasih atas perhatian dan kerjasamanya.<br><strong>Sistem Notifikasi Marimoi</strong></p>
            </div>
        @endif
    </div>
</body>

</html>
