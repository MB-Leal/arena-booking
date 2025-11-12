<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    <!-- FullCalendar CSS/JS Imports -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />

    <style>
        .calendar-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .fc {
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
        }
        /* Define as propriedades de posicionamento para o modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-overlay.hidden {
            display: none !important;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                {{-- NOVO: PLACEHOLDER DINÂMICO PARA NOTIFICAÇÕES --}}
                <div id="realtime-notification">
                    <!-- O banner de pendências será injetado e atualizado periodicamente pelo JavaScript. -->
                </div>
                {{-- FIM DO PLACEHOLDER --}}

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div id="event-modal" class="modal-overlay hidden" onclick="document.getElementById('event-modal').classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
            </div>
            <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="mt-6 w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150">
                Fechar
            </button>
        </div>
    </div>

    <!-- FullCalendar Scripts -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>

    <script>
        /**
         * FUNÇÃO PARA CHECAR AS RESERVAS PENDENTES EM TEMPO REAL (PERIÓDICO)
         * Foi removido o bloco de simulação, utilizando agora a chamada real à API.
         */
        const checkPendingReservations = async () => {
            const notificationContainer = document.getElementById('realtime-notification');
            const apiUrl = '{{ route("api.reservas.pendentes") }}'; // Rota API Laravel

            try {
                // 1. CHAMA O ENDPOINT REAL DA API
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                const count = data.count || 0; // Garante que a contagem é um número ou 0

                let htmlContent = '';

                if (count > 0) {
                    // Alerta Vermelho (Pendências)
                    htmlContent = `
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between transition-all duration-300 transform hover:scale-[1.005]" role="alert">
                            <div class="flex items-start">
                                <svg class="h-6 w-6 flex-shrink-0 mt-0.5 sm:mt-0 mr-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="font-bold text-lg">Atenção: Pendências!</p>
                                    <p class="mt-1 text-sm">Você tem <span class="font-extrabold text-orange-900">${count}</span> pré-reserva(s) aguardando sua ação.</p>
                                </div>
                            </div>
                            <div class="mt-4 sm:mt-0 sm:ml-6">
                                <a href="{{ route('admin.reservas.index') }}" class="inline-block bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white font-bold py-2 px-6 rounded-lg text-sm transition duration-150 ease-in-out shadow-lg">
                                    Revisar Pendências
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    // Alerta Verde (Status OK)
                    htmlContent = `
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                            <div class="flex items-center">
                                <svg class="h-6 w-6 flex-shrink-0 mr-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">Status OK</p>
                                <p class="ml-4 text-sm">Nenhuma pré-reserva pendente. O painel está limpo.</p>
                            </div>
                        </div>
                    `;
                }

                // Injeta o HTML no DOM
                notificationContainer.innerHTML = htmlContent;

            } catch (error) {
                console.error('Erro ao buscar o status de pendências:', error);
                // Exibe uma mensagem de erro de conexão se a API falhar
                notificationContainer.innerHTML = `
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">Erro de Conexão</p>
                        <p class="ml-4 text-sm">Não foi possível carregar o status de pendências em tempo real. ${error.message}</p>
                    </div>
                `;
            }
        };


        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var modal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');

            // 1. Inicializa a checagem de pendências imediatamente e configura o intervalo
            checkPendingReservations();
            // Atualiza a cada 30 segundos (30000 milissegundos)
            setInterval(checkPendingReservations, 30000);

            // INJEÇÃO DINÂMICA FINAL: $eventsJson
            var eventsJson;
            try {
                // Certifica-se de que $eventsJson está sendo injetado corretamente pelo Laravel
                eventsJson = JSON.parse('{!! isset($eventsJson) ? $eventsJson : "[]" !!}');
            } catch (e) {
                console.error("Erro ao parsear $eventsJson. Verifique a saída JSON do Laravel.", e);
                eventsJson = [];
            }

            // [Lógica do FullCalendar]
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',
                views: {
                    dayGridMonth: { buttonText: 'Mês' },
                    timeGridWeek: { buttonText: 'Semana' },
                    timeGridDay: { buttonText: 'Dia' }
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: false,
                events: eventsJson,
                initialDate: new Date().toISOString().slice(0, 10),

                eventClick: function(info) {
                    const startTime = info.event.start;
                    const endTime = info.event.end;

                    const dateOptions = { day: '2-digit', month: '2-digit', year: 'numeric' };
                    const timeOptions = { hour: '2-digit', minute: '2-digit' };

                    const dateDisplay = startTime.toLocaleDateString('pt-BR', dateOptions);

                    let timeDisplay = startTime.toLocaleTimeString('pt-BR', timeOptions);
                    if (endTime) {
                        timeDisplay += ' - ' + endTime.toLocaleTimeString('pt-BR', timeOptions);
                    }

                    const titleParts = info.event.title.split(' - R$ ');
                    const title = titleParts[0];
                    const priceDisplay = titleParts.length > 1 ? `R$ ${titleParts[1]}` : 'N/A';

                    modalContent.innerHTML = `
                        <p class="font-semibold text-gray-900">${title}</p>
                        <p><strong>Data:</strong> ${dateDisplay}</p>
                        <p><strong>Horário:</strong> ${timeDisplay}</p>
                        <p><strong>Valor:</strong> <span class="text-green-600 font-bold">${priceDisplay}</span></p>
                    `;

                    modal.classList.remove('hidden');
                }
            });

            calendar.render();
        };
    </script>
</x-app-layout>
