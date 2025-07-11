@extends('frontend.layouts.main')

@push('styles')
 <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
@endpush

@section('main')
<main class="flex flex-1 bg-base-100">

    <!-- Sidebar Layer Peta -->
    <aside id="sidebar" class="bg-white/95 border-r border-gray-200 p-6 w-72 flex-shrink-0 overflow-y-auto max-h-[calc(100vh-72px)] transition-transform duration-300">
      <div>
          <div id="basemap-section" class="mb-5">
            <button onclick="document.getElementById('sidebar').classList.add('hidden')" class="btn btn-xs btn-outline float-right mb-4">
              ✖ Tutup
            </button>
            <h3 class="font-semibold text-gray-800 mb-3">Base Map</h3>
            <fieldset class="space-y-2">
                <label>
                    <input type="radio" name="basemap" value="osm" checked onclick="changeBaseMap('osm')"> OpenStreetMap
                </label>
            </fieldset>
          </div>

          <div id="layer-section" class="mb-5 hidden">
            <button onclick="document.getElementById('sidebar').classList.add('hidden')" class="btn btn-xs btn-outline float-right mb-4">
              ✖ Tutup
            </button>
            <p class="text-sm font-medium mb-2">Layer Infrastruktur</p>
            <fieldset class="space-y-2 text-gray-700">
                <!-- Checkbox Layer -->
            </fieldset>
          </div>

          <div id="legend-section" class="hidden">
            <button onclick="document.getElementById('sidebar').classList.add('hidden')" class="btn btn-xs btn-outline float-right mb-4">
              ✖ Tutup
            </button>
            <p class="text-sm font-medium mb-2">Legenda</p>
            <ul class="space-y-2 text-sm">
                <!-- Legenda -->
            </ul>
          </div>
      </div>
    </aside>

  
    <!-- Map Area -->
    <section class="flex-grow relative">
      <!-- Search bar in map top center -->
      <form class="absolute top-5 left-1/2 transform -translate-x-1/2 z-30 flex shadow-lg" role="search" aria-label="Pencarian lokasi atau nama proyek">
        <input
          type="search"
          placeholder="Temukan lokasi atau nama proyek..."
          aria-label="Search for location or project name"
          class="input input-bordered rounded-r-none bg-white rounded-lg w-40 md:w-96 focus:outline-blue-600"
        />
        <button
          type="submit"
          aria-label="Cari lokasi atau proyek"
          class="btn bg-gradient-to-bl from-blue-700 to-blue-500 hover:bg-gradient-to-bl hover:from-blue-800 hover:to-blue-600 hover:border-blue-800 rounded-l-none rounded-lg px-4"
        >
          <i class="ph ph-magnifying-glass text-white text-lg"></i>
        </button>
      </form>
  
      <!-- Map controls top right -->
      <div class="absolute top-5 right-5 space-x-2 z-30 flex items-center">
        <button onclick="showSidebarSection('layer')" class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Layer">
          <i class="ph-fill ph-stack" style="color: purple"></i>
        </button>
        <button onclick="showSidebarSection('basemap')" class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Basemap">
          <i class="ph-fill ph-map-trifold" style="color: purple"></i>
        </button>
        <button onclick="showSidebarSection('legend')" class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Legenda">
          <i class="ph-fill ph-list" style="color: purple"></i>
        </button>
      </div>

  
      <!-- Map Area -->
      <div class="w-full h-[calc(100vh-72px)] bg-blue-100 border-l border-gray-200 relative">
        <!-- Map Container -->
        <div id="map" class="w-full h-full z-10 rounded-md"></div>

      </div>

    </section>
</main>
@endsection

@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  
  <script>
    const map = L.map('map').setView([0.36310009945603017, 127.12398149281738], 8);

    let currentBaseMap;

    function changeBaseMap(baseMap) {
      if (currentBaseMap) {
        map.removeLayer(currentBaseMap);
      }

      if (baseMap === 'osm') {
        currentBaseMap = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
      } 
    }

    // Initialize with OpenStreetMap
    changeBaseMap('osm');

    function showSidebarSection(section) {
      const sidebar = document.getElementById('sidebar');
      const layerSection = document.getElementById('layer-section');
      const basemapSection = document.getElementById('basemap-section');
      const legendSection = document.getElementById('legend-section');

      // Tampilkan sidebar kalau disembunyikan
      sidebar.classList.remove('hidden');

      // Reset semua ke hidden
      layerSection.classList.add('hidden');
      basemapSection.classList.add('hidden');
      legendSection.classList.add('hidden');

      // Tampilkan hanya yang dipilih
      if (section === 'layer') layerSection.classList.remove('hidden');
      if (section === 'basemap') basemapSection.classList.remove('hidden');
      if (section === 'legend') legendSection.classList.remove('hidden');
    }
  </script>

@endpush