
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Elite Soccer</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Aplicando o mesmo gradiente usado na sua homepage */
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">


<!-- Container Centralizado (O Card Principal Transparente) -->
<div class="min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-7xl
        p-6 sm:p-10 lg:p-12
        bg-white/95 dark:bg-gray-800/90
        backdrop-blur-md shadow-2xl shadow-gray-900/70 dark:shadow-indigo-900/50
        rounded-3xl transform transition-all duration-300 ease-in-out">
<span>$valor</span>       

        

        
    </div>
</div>




</body>
</html>
