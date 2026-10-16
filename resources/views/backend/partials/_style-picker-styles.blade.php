{{--
    CSS untuk widget "satu simbol" (preview warna/ukuran/ikon + kartu ikon
    bisa dicari) — dipakai di modal Style Layer (spatial-layers/show.blade.php)
    DAN di halaman custom style per Data Spasial
    (spatial-layers/features/edit.blade.php). Pasangannya: _style-picker-script.blade.php.
--}}
<style>
    .layer-style-preview-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 1px solid #dee2e6;
        transition: all 0.15s ease;
    }

    .icon-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        gap: 6px;
        max-height: 240px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background-color: #fff;
    }

    .icon-card-group-title {
        grid-column: 1 / -1;
        margin: 6px 0 2px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #6c757d;
    }

    .icon-card-group-title:first-child {
        margin-top: 0;
    }

    .icon-card-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 8px 4px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: all 0.15s ease;
    }

    .icon-card-item:hover {
        border-color: #0d6efd;
        background-color: #f0f6ff;
    }

    .icon-card-item.active {
        border-color: #0d6efd;
        background-color: #e7f1ff;
        box-shadow: 0 0 0 1px #0d6efd inset;
    }

    .icon-card-item i {
        font-size: 1.3rem;
        color: #495057;
    }

    .icon-card-item span {
        font-size: 0.62rem;
        color: #6c757d;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .icon-card-empty {
        grid-column: 1 / -1;
        text-align: center;
        color: #adb5bd;
        font-size: 0.8rem;
        padding: 20px 0;
    }
</style>
