<!DOCTYPE html>
<html lang="id" class="scroll-smooth" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>MARIMOI | Peta Interaktif</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@2.51.5/dist/full.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@2.51.5/dist/full.css" rel="stylesheet" />
  <link
      rel="stylesheet"
      type="text/css"
      href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css"
    />
    <link
      rel="stylesheet"
      type="text/css"
      href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"
    />
  <style>
    /* Custom scrollbar for sidebar Layer Peta */
    ::-webkit-scrollbar {
      width: 8px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background-color: #94a3b8; /* Tailwind slate-400 */
      border-radius: 10px;
    }
  </style>

  @stack('styles')
</head>
<body class="bg-base-100 min-h-screen flex flex-col">

  <!-- Main Content Area -->
  @yield('main')


  @stack('scripts')
</body>
</html>

