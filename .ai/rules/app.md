---
paths:
  - 'app/**'
---

# App

## Modul "Jenis Peta" sudah dihapus — jangan dibangkitkan
Tabel `map_types` + `map_type_dynamic_attributes`, MapType/MapTypeDynamicAttribute, MapTypeController, MetadataDefinitionController, route `map-types.*` + `metadata-definitions.search`, permission `map-types.manage`, dan command `marimoi:granularize-map-types` dihapus 2026-10-10 (migration `drop_map_types_tables`). Pemicunya: kolom `layers.map_type_id` sudah dilepas 2026-10-06, jadi tidak ada Layer yang bisa dikaitkan ke sebuah Jenis.

Konsekuensi yang harus diingat: "Metadata Dinamis" SELALU kosong. Empat method ini sengaja dipertahankan sebagai titik sambung tunggal yang `return collect()`/`return []` — jangan dihapus, jangan dianggap bug:
- `LayerImportPipeline::activeDynamicAttributesFor()`
- `SpatialLayerFeatureController::activeDynamicAttributesFor()`
- `LayerStyleController::classificationFieldsFor()` (akibatnya style categorized/graduated tak punya kandidat field)
- `SpatialMapController::labeledMetadataDinamis()`

Yang TETAP hidup: katalog `metadata_definitions` (target `layer_attribute_mappings.attribute_definition_id` + command `marimoi:migrate-metadata-definitions`), dan kolom inert `spatial_layers_legacy_v2.map_type_id` (FK-nya dilepas, nilai historis dibiarkan; tak ada kode yang membacanya).
