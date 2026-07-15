<!doctype html>
<html lang="{$config['locale']}" data-bs-theme="auto">

<head>
    <meta charset="utf-8"/>
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta name="theme-color" content="#7c3aed"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>{$config['appName']}</title>
    <script>
        ;(function () {
            const htmlElement = document.querySelector("html")
            const theme = htmlElement.getAttribute("data-bs-theme");

            if(theme === 'dark-auto' || theme === 'auto') {
                function updateTheme() {
                    htmlElement.setAttribute("data-bs-theme",
                        window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light")
                }
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme)
                updateTheme()
            }
        })()
    </script>
    <link href="https://{$config['jsdelivr_url']}/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="/assets/css/tabler-icons.min.css?v=3.31.0" rel="stylesheet"/>
    <link href="/assets/css/gopass.css?v=20260715c" rel="stylesheet"/>
    <script src="/assets/js/fuck.min.js"></script>
    <script src="https://{$config['jsdelivr_url']}/npm/htmx.org@2.0.4/dist/htmx.min.js"></script>
</head>
