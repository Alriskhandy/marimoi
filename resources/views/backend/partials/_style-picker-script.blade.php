{{--
    JS untuk widget "satu simbol" (preview warna/ukuran/ikon + kartu ikon
    bisa dicari) — diekstrak dari modal Style Layer (spatial-layers/show.blade.php)
    supaya bisa dipakai ulang di halaman custom style per Data Spasial
    (spatial-layers/features/edit.blade.php). Pasangannya: _style-picker-styles.blade.php.
    Dipanggil lewat window.initStylePicker({...ids}) di masing-masing pemanggil,
    bukan auto-run — tiap pemanggil beda id elemen & beda name radio marker.
--}}
<script>
    function initStylePicker({
        colorId,
        opacityId,
        opacityValueId,
        sizeId,
        previewCircleId,
        previewIconId,
        iconSelectId,
        iconWrapperId,
        iconGridId,
        iconSearchId,
        markerRadioName,
        initialIconValue,
        scope,
    }) {
        // `scope` membatasi pencarian radio marker ke satu kontainer (mis.
        // satu panel/form) — perlu kalau beberapa instance widget ini hidup
        // berdampingan di halaman yang sama dengan radio BERNAMA SAMA (atribut
        // `name` dipertahankan sama di semua panel supaya field yang
        // ter-submit konsisten, lihat styles/index.blade.php). Default
        // `document` supaya pemanggil lama (satu widget per halaman) tidak
        // perlu berubah.
        scope = scope ?? document;
        const colorInput = document.getElementById(colorId);
        const opacityInput = document.getElementById(opacityId);
        const opacityValueEl = document.getElementById(opacityValueId);
        const sizeInput = document.getElementById(sizeId);
        const previewCircle = document.getElementById(previewCircleId);
        const previewIcon = document.getElementById(previewIconId);
        const iconSelect = iconSelectId ? document.getElementById(iconSelectId) : null;
        const iconWrapper = iconWrapperId ? document.getElementById(iconWrapperId) : null;
        const iconGrid = iconGridId ? document.getElementById(iconGridId) : null;
        const iconSearch = iconSearchId ? document.getElementById(iconSearchId) : null;

        if (!colorInput || !previewCircle) {
            return;
        }

        if (iconSelect) {
            iconSelect.value = initialIconValue ?? '';
        }

        function updateStylePreview() {
            const color = colorInput.value;
            const opacity = opacityInput.value;
            const size = parseFloat(sizeInput.value) || 6;
            const isMarker = markerRadioName ?
                scope.querySelector(`input[name="${markerRadioName}"]:checked`)?.value === '1' :
                false;
            const icon = iconSelect ? iconSelect.value : '';

            previewCircle.style.width = (size * 4) + 'px';
            previewCircle.style.height = (size * 4) + 'px';
            previewCircle.style.opacity = opacity;
            if (opacityValueEl) {
                opacityValueEl.textContent = opacity;
            }

            if (isMarker && icon) {
                previewCircle.style.backgroundColor = 'transparent';
                previewIcon.className = icon;
                previewIcon.style.color = color;
                previewIcon.style.fontSize = (size * 2) + 'px';
                previewIcon.style.display = '';
            } else {
                previewCircle.style.backgroundColor = color;
                if (previewIcon) {
                    previewIcon.style.display = 'none';
                }
            }

            if (iconWrapper) {
                iconWrapper.style.display = isMarker ? '' : 'none';
            }

            if (iconGrid) {
                iconGrid.querySelectorAll('.icon-card-item').forEach((card) => {
                    card.classList.toggle('active', card.dataset.iconValue === icon);
                });
            }
        }

        function appendIconCard(option) {
            const value = option.value;
            if (!value) {
                return false;
            }

            const label = option.textContent.trim();
            const card = document.createElement('div');
            card.className = 'icon-card-item';
            card.dataset.iconValue = value;
            card.dataset.search = (label + ' ' + value).toLowerCase();
            card.title = label;
            card.innerHTML = `<i class="${value}"></i><span>${label}</span>`;

            card.addEventListener('click', () => {
                iconSelect.value = value;
                updateStylePreview();
            });

            iconGrid.appendChild(card);

            return true;
        }

        function buildIconCardGrid() {
            if (!iconGrid || !iconSelect) {
                return;
            }

            iconGrid.innerHTML = '';
            let itemCount = 0;

            iconSelect.querySelectorAll('option, optgroup').forEach((node) => {
                if (node.tagName === 'OPTGROUP') {
                    const title = document.createElement('div');
                    title.className = 'icon-card-group-title';
                    title.textContent = node.label;
                    iconGrid.appendChild(title);

                    node.querySelectorAll('option').forEach((option) => {
                        itemCount += appendIconCard(option) ? 1 : 0;
                    });
                } else if (node.value) {
                    itemCount += appendIconCard(node) ? 1 : 0;
                }
            });

            if (itemCount === 0) {
                iconGrid.innerHTML = '<div class="icon-card-empty">Tidak ada ikon tersedia</div>';
            }
        }

        buildIconCardGrid();

        if (iconSearch) {
            iconSearch.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                iconGrid.querySelectorAll('.icon-card-item').forEach((card) => {
                    card.style.display = (!query || card.dataset.search.includes(query)) ? '' : 'none';
                });

                iconGrid.querySelectorAll('.icon-card-group-title').forEach((title) => {
                    let node = title.nextElementSibling;
                    let hasVisible = false;
                    while (node && !node.classList.contains('icon-card-group-title')) {
                        if (node.classList.contains('icon-card-item') && node.style.display !== 'none') {
                            hasVisible = true;
                        }
                        node = node.nextElementSibling;
                    }
                    title.style.display = hasVisible ? '' : 'none';
                });
            });
        }

        [colorInput, opacityInput, sizeInput].forEach((el) => {
            el && el.addEventListener('input', updateStylePreview);
        });

        if (markerRadioName) {
            scope.querySelectorAll(`input[name="${markerRadioName}"]`).forEach((el) => {
                el.addEventListener('change', updateStylePreview);
            });
        }

        updateStylePreview();
    }
</script>
