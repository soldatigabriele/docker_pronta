<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Pronta</title>
        
        <!-- PWA Meta Tags -->
        <meta name="description" content="A modern, collaborative todo list application with real-time updates">
        <meta name="theme-color" content="#0d6efd">
        <meta name="background-color" content="#ffffff">
        
        <!-- Apple Meta Tags -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Pronta">
        
        <!-- Microsoft Tiles -->
        <meta name="msapplication-TileColor" content="#0d6efd">
        <meta name="msapplication-config" content="/browserconfig.xml">
        
        <!-- Icons -->
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="mask-icon" href="/mask-icon.svg" color="#0d6efd">
        
        <!-- PWA Manifest -->
        <link rel="manifest" href="/build/manifest.json">

        @vite(['resources/js/app.js'])

    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
