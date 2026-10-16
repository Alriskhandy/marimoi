{{-- Ilustrasi animasi tiap langkah alur data. Animasi berjalan saat panel aktif (group ber-data-on). --}}
<svg viewBox="0 0 200 160" class="h-full w-full overflow-visible" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($i)
        @case(0)
            {{-- Data: basis data --}}
            <g stroke="#20D9FF" stroke-width="2">
                <ellipse cx="100" cy="38" rx="54" ry="15" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw"/>
                <path d="M46 38v32c0 8 24 15 54 15s54-7 54-15V38" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.15s]"/>
                <path d="M46 70v32c0 8 24 15 54 15s54-7 54-15V70" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.3s]"/>
                <path d="M46 102v20c0 8 24 15 54 15s54-7 54-15v-20" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.45s]"/>
            </g>
            <g fill="#4DE1C1" stroke="none">
                <circle cx="76" cy="60" r="3" class="group-data-[on=true]:animate-blink"/>
                <circle cx="100" cy="64" r="3" class="group-data-[on=true]:animate-blink [animation-delay:.4s]"/>
                <circle cx="124" cy="60" r="3" class="group-data-[on=true]:animate-blink [animation-delay:.8s]"/>
                <circle cx="76" cy="92" r="3" class="group-data-[on=true]:animate-blink [animation-delay:1.1s]"/>
                <circle cx="100" cy="96" r="3" class="group-data-[on=true]:animate-blink [animation-delay:.2s]"/>
                <circle cx="124" cy="92" r="3" class="group-data-[on=true]:animate-blink [animation-delay:.6s]"/>
            </g>
            @break
        @case(1)
            {{-- Spasial: grid + pin --}}
            <g stroke="rgba(32,217,255,.35)" stroke-width="1">
                <path d="M20 120 60 60h120M50 140l40-60h100M100 145l32-85M150 145l24-85M20 92l40-32"/>
                <path d="M36 100h150M52 120h150M75 80h120" />
            </g>
            <g class="group-data-[on=true]:animate-drop [transform-box:fill-box]">
                <path d="M100 22c-16 0-28 12-28 27 0 20 28 47 28 47s28-27 28-47c0-15-12-27-28-27Z" fill="#0A84FF" stroke="#20D9FF" stroke-width="2"/>
                <circle cx="100" cy="49" r="9" fill="#061522"/>
            </g>
            <ellipse cx="100" cy="102" rx="26" ry="8" stroke="#20D9FF" stroke-width="1.5" class="origin-center [transform-box:fill-box] group-data-[on=true]:animate-ring"/>
            @break
        @case(2)
            {{-- Program: percabangan --}}
            <g stroke="#20D9FF" stroke-width="2">
                <path d="M100 48v26M100 74C100 96 44 90 44 116M100 74v42M100 74c0 22 56 16 56 42" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.2s]"/>
            </g>
            <circle cx="100" cy="36" r="13" fill="#0A84FF" stroke="#20D9FF" stroke-width="2" class="group-data-[on=true]:animate-pop [transform-box:fill-box] origin-center"/>
            <g fill="#061522" stroke="#4DE1C1" stroke-width="2">
                <circle cx="44" cy="126" r="10" class="group-data-[on=true]:animate-pop [animation-delay:.8s] [transform-box:fill-box] origin-center"/>
                <circle cx="100" cy="126" r="10" class="group-data-[on=true]:animate-pop [animation-delay:1s] [transform-box:fill-box] origin-center"/>
                <circle cx="156" cy="126" r="10" class="group-data-[on=true]:animate-pop [animation-delay:1.2s] [transform-box:fill-box] origin-center"/>
            </g>
            @break
        @case(3)
            {{-- Pembangunan: balok naik --}}
            <path d="M24 136h152" stroke="rgba(32,217,255,.5)" stroke-width="2"/>
            <g fill="#0A84FF" stroke="#20D9FF" stroke-width="1.5">
                <rect x="34" y="92" width="26" height="44" rx="3" class="origin-bottom [transform-box:fill-box] group-data-[on=true]:animate-rise"/>
                <rect x="70" y="64" width="26" height="72" rx="3" class="origin-bottom [transform-box:fill-box] group-data-[on=true]:animate-rise [animation-delay:.15s]"/>
                <rect x="106" y="40" width="26" height="96" rx="3" class="origin-bottom [transform-box:fill-box] group-data-[on=true]:animate-rise [animation-delay:.3s]"/>
                <rect x="142" y="76" width="26" height="60" rx="3" class="origin-bottom [transform-box:fill-box] group-data-[on=true]:animate-rise [animation-delay:.45s]"/>
            </g>
            <g fill="#4DE1C1" stroke="none">
                <circle cx="119" cy="28" r="4" class="group-data-[on=true]:animate-blink"/>
            </g>
            @break
        @case(4)
            {{-- Monitoring: denyut data --}}
            <g stroke="rgba(32,217,255,.25)" stroke-width="1"><path d="M20 40h160M20 80h160M20 120h160"/></g>
            <path d="M14 82h42l14-38 22 76 20-56 12 18h62" pathLength="1" stroke="#20D9FF" stroke-width="3" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw-loop"/>
            <circle cx="186" cy="82" r="5" fill="#4DE1C1" class="group-data-[on=true]:animate-blink"/>
            @break
        @default
            {{-- Keputusan: target + centang --}}
            <g stroke="#20D9FF" stroke-width="2">
                <circle cx="100" cy="80" r="56" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw"/>
                <circle cx="100" cy="80" r="38" stroke="rgba(32,217,255,.5)" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.2s]"/>
            </g>
            <path d="m78 82 16 16 30-34" stroke="#4DE1C1" stroke-width="6" pathLength="1" class="[stroke-dasharray:1] group-data-[on=true]:animate-draw [animation-delay:.7s]"/>
    @endswitch
</svg>
