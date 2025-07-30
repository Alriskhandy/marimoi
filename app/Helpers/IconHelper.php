<?php
// File: app/Helpers/IconHelper.php

class IconHelper
{
    /**
     * Konversi FontAwesome 6 ke FontAwesome 4 untuk tampilan
     */
    public static function convertFa6ToFa4($fa6Class)
    {
        if (empty($fa6Class)) {
            return '';
        }

        $iconMappings = [
            // Lokasi & Navigasi
            'fa-solid fa-location-dot' => 'fa fa-map-marker',
            'fa-solid fa-map-pin' => 'fa fa-thumb-tack',
            'fa-solid fa-compass' => 'fa fa-compass',
            'fa-solid fa-route' => 'fa fa-road',
            'fa-solid fa-crosshairs' => 'fa fa-crosshairs',
            'fa-solid fa-map-marker-alt' => 'fa fa-map-marker',
            'fa-solid fa-directions' => 'fa fa-location-arrow',

            // Pemerintahan & Fasilitas Publik
            'fa-solid fa-landmark' => 'fa fa-university',
            'fa-solid fa-university' => 'fa fa-university',
            'fa-solid fa-building' => 'fa fa-building',
            'fa-solid fa-building-columns' => 'fa fa-bank',
            'fa-solid fa-scale-balanced' => 'fa fa-balance-scale',
            'fa-solid fa-shield-halved' => 'fa fa-shield',
            'fa-solid fa-flag' => 'fa fa-flag',
            'fa-solid fa-city' => 'fa fa-building-o',

            // Kesehatan & Pendidikan
            'fa-solid fa-hospital' => 'fa fa-hospital-o',
            'fa-solid fa-user-doctor' => 'fa fa-user-md',
            'fa-solid fa-pills' => 'fa fa-medkit',
            'fa-solid fa-school' => 'fa fa-university',
            'fa-solid fa-graduation-cap' => 'fa fa-graduation-cap',
            'fa-solid fa-book' => 'fa fa-book',
            'fa-solid fa-heartbeat' => 'fa fa-heartbeat',
            'fa-solid fa-stethoscope' => 'fa fa-stethoscope',

            // Transportasi
            'fa-solid fa-car' => 'fa fa-car',
            'fa-solid fa-bus' => 'fa fa-bus',
            'fa-solid fa-train' => 'fa fa-train',
            'fa-solid fa-plane' => 'fa fa-plane',
            'fa-solid fa-ship' => 'fa fa-ship',
            'fa-solid fa-gas-pump' => 'fa fa-car',
            'fa-solid fa-motorcycle' => 'fa fa-motorcycle',
            'fa-solid fa-taxi' => 'fa fa-taxi',
            'fa-solid fa-parking' => 'fa fa-car',

            // Perdagangan & Ekonomi
            'fa-solid fa-store' => 'fa fa-shopping-bag',
            'fa-solid fa-shopping-cart' => 'fa fa-shopping-cart',
            'fa-solid fa-utensils' => 'fa fa-cutlery',
            'fa-solid fa-coffee' => 'fa fa-coffee',
            'fa-solid fa-warehouse' => 'fa fa-building',
            'fa-solid fa-industry' => 'fa fa-industry',
            'fa-solid fa-shopping-bag' => 'fa fa-shopping-bag',
            'fa-solid fa-cash-register' => 'fa fa-credit-card',

            // Lingkungan & Alam
            'fa-solid fa-tree' => 'fa fa-tree',
            'fa-solid fa-mountain' => 'fa fa-mountain',
            'fa-solid fa-water' => 'fa fa-tint',
            'fa-solid fa-seedling' => 'fa fa-leaf',
            'fa-solid fa-leaf' => 'fa fa-leaf',
            'fa-solid fa-sun' => 'fa fa-sun-o',
            'fa-solid fa-cloud-rain' => 'fa fa-cloud',
            'fa-solid fa-snowflake' => 'fa fa-snowflake-o',

            // Infrastruktur
            'fa-solid fa-tower-broadcast' => 'fa fa-signal',
            'fa-solid fa-bolt' => 'fa fa-bolt',
            'fa-solid fa-wrench' => 'fa fa-wrench',
            'fa-solid fa-road' => 'fa fa-road',
            'fa-solid fa-bridge' => 'fa fa-building',
            'fa-solid fa-tower-cell' => 'fa fa-signal',
            'fa-solid fa-wifi' => 'fa fa-wifi',
            'fa-solid fa-satellite-dish' => 'fa fa-wifi',

            // Olahraga & Rekreasi
            'fa-solid fa-football' => 'fa fa-soccer-ball-o',
            'fa-solid fa-dumbbell' => 'fa fa-dumbbell',
            'fa-solid fa-swimmer' => 'fa fa-life-ring',
            'fa-solid fa-person-hiking' => 'fa fa-male',
            'fa-solid fa-tent' => 'fa fa-home',
            'fa-solid fa-camera' => 'fa fa-camera',
            'fa-solid fa-volleyball' => 'fa fa-circle-o',
            'fa-solid fa-table-tennis-paddle-ball' => 'fa fa-circle',

            // Keagamaan
            'fa-solid fa-mosque' => 'fa fa-building',
            'fa-solid fa-church' => 'fa fa-building',
            'fa-solid fa-place-of-worship' => 'fa fa-building',
            'fa-solid fa-cross' => 'fa fa-plus',
            'fa-solid fa-om' => 'fa fa-circle-o',
            'fa-solid fa-dharmachakra' => 'fa fa-circle-o',

            // Keamanan & Darurat
            'fa-solid fa-fire-flame-curved' => 'fa fa-fire',
            'fa-solid fa-truck-medical' => 'fa fa-ambulance',
            'fa-solid fa-shield' => 'fa fa-shield',
            'fa-solid fa-siren-on' => 'fa fa-volume-up',
            'fa-solid fa-life-ring' => 'fa fa-life-ring',
            'fa-solid fa-triangle-exclamation' => 'fa fa-warning',
            'fa-solid fa-hard-hat' => 'fa fa-user',

            // Pariwisata & Budaya
            'fa-solid fa-monument' => 'fa fa-building',
            'fa-solid fa-museum' => 'fa fa-university',
            'fa-solid fa-ticket' => 'fa fa-ticket',
            'fa-solid fa-map' => 'fa fa-map',
            'fa-solid fa-binoculars' => 'fa fa-search',
            'fa-solid fa-mountain-sun' => 'fa fa-mountain',
            'fa-solid fa-masks-theater' => 'fa fa-music',
            'fa-solid fa-star' => 'fa fa-star',

            // Utilitas & Layanan
            'fa-solid fa-trash' => 'fa fa-trash',
            'fa-solid fa-recycle' => 'fa fa-recycle',
            'fa-solid fa-toilet' => 'fa fa-home',
            'fa-solid fa-faucet' => 'fa fa-tint',
            'fa-solid fa-hammer' => 'fa fa-wrench',
            'fa-solid fa-tools' => 'fa fa-wrench',
            'fa-solid fa-envelope' => 'fa fa-envelope',
            'fa-solid fa-phone' => 'fa fa-phone',
        ];

        return $iconMappings[$fa6Class] ?? 'fa fa-question-circle';
    }

    /**
     * Generate HTML untuk icon dengan konversi otomatis
     */
    public static function renderIcon($fa6Class, $size = '', $color = '', $additionalClasses = '')
    {
        if (empty($fa6Class)) {
            return '';
        }

        $fa4Class = self::convertFa6ToFa4($fa6Class);
        $sizeClass = $size ? " fa-{$size}" : '';
        $style = $color ? " style=\"color: {$color};\"" : '';
        $classes = $additionalClasses ? " {$additionalClasses}" : '';

        return "<i class=\"{$fa4Class}{$sizeClass}{$classes}\"{$style}></i>";
    }

    /**
     * Generate HTML untuk icon dengan tooltip yang menunjukkan class asli
     */
    public static function renderIconWithTooltip($fa6Class, $size = '', $color = '', $additionalClasses = '')
    {
        if (empty($fa6Class)) {
            return '';
        }

        $fa4Class = self::convertFa6ToFa4($fa6Class);
        $sizeClass = $size ? " fa-{$size}" : '';
        $style = $color ? " style=\"color: {$color};\"" : '';
        $classes = $additionalClasses ? " {$additionalClasses}" : '';

        return "<i class=\"{$fa4Class}{$sizeClass}{$classes}\" title=\"Saved as: {$fa6Class}\"{$style}></i>";
    }
}