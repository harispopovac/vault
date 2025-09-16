<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>template</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
    @vite(['resources/js/main.js'])
</head>

<body>
<div id="app">
</div>

<script>
    const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'

    if (loaderColor)
        document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
    if (loaderColor)
        document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
        document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
</script>
</body>
</html>
