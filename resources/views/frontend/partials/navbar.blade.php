<header class="w-full border-b border-gray-200 bg-white sticky top-0 z-50">
    <nav class="container mx-auto flex items-center justify-between py-3 px-4 md:px-6">
      <div class="flex items-center space-x-3 min-w-max">
        <img
          src="{{ asset('assets/img/logo.svg') }}"
          alt="Logo Bappeda Provinsi Maluku Utara, shield emblem with green, red and yellow colors and text MARIMOI"
          class="w-12 h-12 object-contain"/>
        <div class="flex flex-col">
          <a href="#" class="font-extrabold text-blue-800 text-lg hover:underline">MARIMOI</a>
          <p class="text-sm text-gray-500 leading-tight">Bappeda Provinsi Maluku Utara</p>
        </div>
      </div>

     <ul class="hidden md:flex space-x-6 font-semibold text-gray-700 text-sm">
        <li>
          <a href="{{ route('beranda') }}"
            class="{{ request()->routeIs('beranda') ? 'text-blue-800 font-bold' : 'hover:text-blue-700 transition' }}">
            BERANDA
          </a>
        </li>
        <li>
          <a href="{{ route('tampil.peta') }}"
            class="{{ request()->routeIs('tampil.peta') ? 'text-blue-800 font-bold' : 'hover:text-blue-700 transition' }}">
            PETA SIG
          </a>
        </li>
      </ul>


      <div class="min-w-max">
        <a href="{{ route('login') }}">

          <button class="btn btn-warning btn-sm normal-case px-5 rounded-lg">LOGIN OPD</button>
        </a>
      </div>
    </nav>
  </header>