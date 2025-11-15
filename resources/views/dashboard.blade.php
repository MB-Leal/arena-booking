<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    {{-- IMPORTAÇÕES (Mantidas do seu código original) --}}
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

        /* Estilo para Eventos Reservados (Azul) */
        .fc-event-booked {
            background-color: #4f46e5 !important; /* Indigo 600 */
            border-color: #4338ca !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 4px;
        }

        /* Estilo para Eventos Disponíveis (Verde) */
        .fc-event-available {
            background-color: #10B981 !important; /* Verde 500 */
            border-color: #059669 !important;
            color: white !important;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            opacity: 0.8;
            transition: opacity 0.2s;
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

                @if (session('warning'))
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('warning') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif


                {{-- PLACEHOLDER DINÂMICO PARA NOTIFICAÇÕES --}}
                <div id="realtime-notification">
                </div>
                {{-- FIM DO PLACEHOLDER --}}

                {{-- Legenda para explicar as cores --}}
                <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium">
                    <div class="flex items-center p-2 bg-indigo-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-indigo-600 mr-2"></span>
                        <span>Reservado (Confirmado)</span>
                    </div>
                    <div class="flex items-center p-2 bg-green-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-green-500 mr-2"></span>
                        <span>Disponível (Horários Abertos)</span>
                    </div>
                </div>

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Detalhes de Reserva (RESERVAS EXISTENTES) --}}
    {{-- Mantido o ID original para o fluxo do seu JS --}}
    <div id="event-modal" class="modal-overlay hidden" onclick="closeEventModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
            </div>
            <div class="mt-6 w-full space-y-2" id="modal-actions">
                {{-- Botões injetados pelo JS --}}
                <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    {{-- ✅ NOVO MODAL (para o Motivo do Cancelamento) --}}
    <div id="cancellation-modal" class="modal-overlay hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 id="modal-title-cancel" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>

            <p id="modal-message-cancel" class="text-gray-700 mb-4 font-medium"></p>

            <div class="mb-6">
                <label for="cancellation-reason-input" class="block text-sm font-medium text-gray-700 mb-2">
                    Motivo do Cancelamento:
                </label>
                <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo do cancelamento (mínimo 5 caracteres)..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Fechar
                </button>
                <button id="confirm-cancellation-btn" type="button" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Confirmar Ação
                </button>
            </div>
        </div>
    </div>


    {{-- Modal de Agendamento Rápido (SLOTS DISPONÍVEIS) --}}
    <div id="quick-booking-modal" class="modal-overlay hidden" onclick="document.getElementById('quick-booking-modal').classList.add('hidden')">
        {{-- 🛑 REMOVIDA A PROPRIEDADE 'action' e 'method' DO FORM HTML --}}
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-green-700 mb-4 border-b pb-2">Agendamento Rápido de Slot</h3>

            <form id="quick-booking-form">
                @csrf
                {{-- O token CSRF deve estar presente para o JS pegar, mas o action e method são desnecessários agora --}}

                <div id="slot-info-display" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                </div>

                <input type="hidden" name="schedule_id" id="quick-schedule-id">
                <input type="hidden" name="date" id="quick-date">
                <input type="hidden" name="start_time" id="quick-start-time">
                <input type="hidden" name="end_time" id="quick-end-time">
                <input type="hidden" name="price" id="quick-price">
                <input type="hidden" name="reserva_id_to_update" id="reserva-id-to-update">

                <div class="mb-4">
                    <label for="client_name" class="block text-sm font-medium text-gray-700">Nome do Cliente *</label>
                    <input type="text" name="client_name" id="client_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label for="client_contact" class="block text-sm font-medium text-gray-700">Contato (Telefone/Email) *</label>
                    <input type="text" name="client_contact" id="client_contact" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- ✅ CHECKBOX PARA RECORRÊNCIA --}}
                <div class="mb-4 p-3 border border-indigo-200 rounded-lg bg-indigo-50">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_recurrent" id="is-recurrent" value="1"
                               class="h-5 w-5 text-indigo-600 border-indigo-300 rounded focus:ring-indigo-500">
                        <label for="is-recurrent" class="ml-3 text-base font-semibold text-indigo-700">
                            Tornar esta reserva Recorrente (Anual)
                        </label>
                    </div>
                    <p class="text-xs text-indigo-600 mt-1 pl-8">
                        Ao marcar, todos os slots futuros desta faixa de horário serão reservados para este cliente.
                    </p>
                </div>
                {{-- FIM DO NOVO CHECKBOX --}}

                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Observações (Opcional)</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <button type="submit" id="submit-quick-booking" class="mt-4 w-full px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition duration-150">
                    Confirmar Agendamento
                </button>
                <button type="button" onclick="document.getElementById('quick-booking-modal').classList.add('hidden')" class="mt-2 w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                    Cancelar
                </button>
            </form>
        </div>
    </div>


    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>


    <script>
        // === CONFIGURAÇÕES E ROTAS ===
        const PENDING_API_URL = '{{ route("api.reservas.pendentes") }}';
        const RESERVED_API_URL = '{{ route("api.reservas.confirmadas") }}';
        const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';
        const SHOW_RESERVA_URL = '{{ route("admin.reservas.show", ":id") }}'; // Rota para detalhes/gerenciamento

        // ROTAS DE SUBMISSÃO
        const RECURRENT_STORE_URL = '{{ route("api.reservas.store_recurrent") }}';
        const QUICK_STORE_URL = '{{ route("api.reservas.store_quick") }}';

        // ROTAS DE CANCELAMENTO (POST para enviar o motivo no body)
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}';
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}';
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}';
        // ======================================

        // TOKEN CSRF
        const csrfToken = document.querySelector('input[name="_token"]').value;

        // VARIÁVEIS GLOBAIS DE ESTADO
        let calendar; // Instância do FullCalendar
        let currentReservaId = null;
        let currentMethod = null;
        let currentUrlBase = null;


        /**
         * FUNÇÃO PARA CHECAR AS RESERVAS PENDENTES EM TEMPO REAL (PERIÓDICO)
         */
        const checkPendingReservations = async () => {
            const notificationContainer = document.getElementById('realtime-notification');
            const apiUrl = PENDING_API_URL;

            try {
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                const count = data.count || 0;
                let htmlContent = '';

                if (count > 0) {
                    // Alerta Laranja (Pendências)
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

                notificationContainer.innerHTML = htmlContent;

            } catch (error) {
                console.error('Erro ao buscar o status de pendências:', error);
                notificationContainer.innerHTML = `
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">Erro de Conexão</p>
                        <p class="ml-4 text-sm">Não foi possível carregar o status de pendências em tempo real. ${error.message}</p>
                    </div>
                `;
            }
        };

        // =========================================================
        // ✅ FUNÇÃO CRÍTICA: Lidar com a submissão do Agendamento Rápido via AJAX
        // =========================================================
        async function handleQuickBookingSubmit(event) {
            event.preventDefault(); // CRÍTICO: Previne a navegação de página

            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const isRecurrent = document.getElementById('is-recurrent').checked;

            // Altera a URL de destino com base no checkbox de recorrência
            const targetUrl = isRecurrent ? RECURRENT_STORE_URL : QUICK_STORE_URL;

            const submitBtn = document.getElementById('submit-quick-booking');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Agendando...';

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500).", errorText);
                    alert(`Erro do Servidor (${response.status}). Verifique o console.`);
                    return;
                }

                if (response.ok && result.success) {
                    alert(result.message);
                    // Fecha o modal
                    document.getElementById('quick-booking-modal').classList.add('hidden');

                    // Recarrega a página para garantir a atualização visual (Azul -> Verde ou Vice-versa)
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                    // Erros de validação (ex: nome do cliente faltando)
                    const errors = Object.values(result.errors).flat().join('\n');
                    alert(`ERRO DE VALIDAÇÃO:\n${errors}`);
                } else {
                    // Erros como Conflito (409)
                    alert(result.message || `Erro desconhecido. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede:', error);
                alert("Erro de conexão. Tente novamente.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Agendamento';
            }
        }

        // =========================================================
        // ✅ NOVO FLUXO DE CANCELAMENTO (Motivo)
        // =========================================================

        function closeEventModal() {
            document.getElementById('event-modal').classList.add('hidden');
        }

        /**
         * Abre o modal de cancelamento e configura os dados da reserva.
         */
        function openCancellationModal(reservaId, method, urlBase, message, buttonText) {
            // Fecha o modal de detalhes para abrir o de cancelamento
            closeEventModal();

            currentReservaId = reservaId;
            currentMethod = method;
            currentUrlBase = urlBase;
            document.getElementById('cancellation-reason-input').value = ''; // Limpa o campo

            document.getElementById('modal-message-cancel').textContent = message;
            document.getElementById('cancellation-modal').classList.remove('hidden');

            // Ativa a transição do modal (opcional, dependendo do seu CSS)
            setTimeout(() => {
                document.getElementById('cancellation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);

            document.getElementById('confirm-cancellation-btn').textContent = buttonText;
        }

        /**
         * Fecha o modal de cancelamento.
         */
        function closeCancellationModal() {
            document.getElementById('cancellation-modal').classList.add('hidden');
        }


        /**
         * FUNÇÃO AJAX GENÉRICA PARA CANCELAMENTO
         */
        async function sendCancellationRequest(reservaId, method, urlBase, reason) {
            const url = urlBase.replace(':id', reservaId);

            // LOG DE DEBUG PARA VER O QUE ESTÁ SENDO ENVIADO
            console.log(`[DEBUG] Tentando enviar AJAX para: ${url}`);
            console.log(`[DEBUG] Método Lógico (_method): ${method}`);
            console.log(`[DEBUG] Motivo: ${reason}`);

            const bodyData = {
                cancellation_reason: reason,
                _token: csrfToken,
            };

            // 🛑 O _method foi removido de todos os fluxos para evitar o 405.

            const fetchConfig = {
                // ✅ CRÍTICO: O método de transporte DEVE ser POST para a rota Laravel.
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(bodyData)
            };

            const submitBtn = document.getElementById('confirm-cancellation-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            try {
                const response = await fetch(url, fetchConfig);

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", errorText);
                    result = { error: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok) {
                    alert(result.message || "Ação realizada com sucesso. O calendário será atualizado.");
                    closeCancellationModal();

                    // 🛑 AQUI ESTÁ A MUDANÇA: FORÇA A RECARGA DA PÁGINA PARA GARANTIR
                    // A ATUALIZAÇÃO VISUAL APÓS OPERAÇÕES DE DELEÇÃO/RECRIÇÃO DE SLOTS
                    setTimeout(() => {
                         window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                     const reasonError = result.errors.cancellation_reason ? result.errors.cancellation_reason.join(', ') : 'Erro de validação desconhecido.';
                     alert(`ERRO DE VALIDAÇÃO: ${reasonError}`);
                } else {
                    // Se a resposta for 405 ou outro erro, o result.error não será JSON,
                    // mas o log acima já nos deu a pista (405).
                    alert(result.error || result.message || `Erro desconhecido ao processar a ação. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                alert("Erro de conexão. Tente novamente.");
            } finally {
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Confirmar Ação';
            }
        }

        // --- Listener de Confirmação do Modal de Cancelamento ---
        document.getElementById('confirm-cancellation-btn').addEventListener('click', function() {
            const reason = document.getElementById('cancellation-reason-input').value.trim();

            // Validação mínima do Front-end (o Controller fará a validação final)
            if (reason.length < 5) {
                alert("Por favor, forneça um motivo de cancelamento com pelo menos 5 caracteres.");
                return;
            }

            if (currentReservaId && currentMethod && currentUrlBase) {
                // Note que enviamos 'PATCH' ou 'DELETE' como método LÓGICO, mas o AJAX será POST
                sendCancellationRequest(currentReservaId, currentMethod, currentUrlBase, reason);
            } else {
                alert("Erro: Dados da reserva não configurados corretamente.");
            }
        });

        // --- Funções Chamadas pelos Botões do #event-modal ---
        // Funções específicas de Cancelamento (Expostas globalmente/ao window.onload)
        const cancelarPontual = (id, isRecurrent) => {
            const urlBase = isRecurrent ? CANCEL_PONTUAL_URL : CANCEL_PADRAO_URL;
            // O método LÓGICO é PATCH/DELETE, mas o transporte será POST para a rota específica
            const method = isRecurrent ? 'DELETE' : 'PATCH';
            const confirmation = isRecurrent
                ? "Cancelar SOMENTE ESTA reserva? O slot será liberado pontualmente."
                : "Cancelar esta reserva pontual (Status mudará para 'Cancelada').";
            const buttonText = isRecurrent ? 'Cancelar ESTE DIA' : 'Confirmar Cancelamento';

            openCancellationModal(id, method, urlBase, confirmation, buttonText);
        };

        const cancelarSerie = (id) => {
            const urlBase = CANCEL_SERIE_URL;
            const method = 'DELETE';
            const confirmation = "⚠️ ATENÇÃO: Cancelar TODA A SÉRIE desta reserva? Todos os horários futuros serão liberados.";
            const buttonText = 'Confirmar Cancelamento de SÉRIE';

            openCancellationModal(id, method, urlBase, confirmation, buttonText);
        };

        // =========================================================


        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var eventModal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');
            var modalActions = document.getElementById('modal-actions');
            const quickBookingForm = document.getElementById('quick-booking-form');

            // 1. Inicializa a checagem de pendências imediatamente e configura o intervalo
            checkPendingReservations();
            setInterval(checkPendingReservations, 30000);

            // 🛑 NOVO: Adiciona o listener para a submissão AJAX do agendamento rápido
            quickBookingForm.addEventListener('submit', handleQuickBookingSubmit);


            // [Lógica do FullCalendar]
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',

                eventSources: [
                    // 1. Fonte de Reservas Confirmadas (Eventos Azuis)
                    {
                        url: RESERVED_API_URL,
                        method: 'GET',
                        failure: function() {
                            console.error('Falha ao carregar reservas confirmadas via API.');
                        },
                        className: 'fc-event-booked',
                        textColor: 'white'
                    },
                    // 2. Fonte de Horários Disponíveis (Eventos Verdes)
                    {
                        url: AVAILABLE_API_URL,
                        method: 'GET',
                        failure: function() {
                            console.error('Falha ao carregar horários disponíveis via API.');
                        },
                        className: 'fc-event-available',
                        display: 'block'
                    }
                ],

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
                initialDate: new Date().toISOString().slice(0, 10),

                eventClick: function(info) {
                    const event = info.event;
                    const isAvailable = event.classNames.includes('fc-event-available');

                    // --- LÓGICA DE SLOT DISPONÍVEL (Agendamento Rápido) ---
                    if (isAvailable) {
                        const quickBookingModal = document.getElementById('quick-booking-modal');

                        const startDate = moment(event.start);
                        const endDate = moment(event.end);

                        const dateString = startDate.format('YYYY-MM-DD');
                        const dateDisplay = startDate.format('DD/MM/YYYY');

                        const startTimeInput = startDate.format('H:mm');
                        const endTimeInput = endDate.format('H:mm');

                        const timeSlotDisplay = startTimeInput + ' - ' + endTimeInput;

                        const extendedProps = event.extendedProps || {};
                        const price = extendedProps.price || 0;

                        const reservaIdToUpdate = event.id;

                        // 1. Preencher os campos ocultos do modal (para envio ao servidor)
                        document.getElementById('reserva-id-to-update').value = reservaIdToUpdate;
                        document.getElementById('quick-date').value = dateString;
                        document.getElementById('quick-start-time').value = startTimeInput;
                        document.getElementById('quick-end-time').value = endTimeInput;
                        document.getElementById('quick-price').value = price;

                        // Limpa campos do cliente e checkbox de recorrência
                        document.getElementById('notes').value = '';
                        document.getElementById('client_name').value = '';
                        document.getElementById('client_contact').value = '';
                        document.getElementById('is-recurrent').checked = false;

                        // 2. Injetar a informação visível
                        document.getElementById('slot-info-display').innerHTML = `
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeSlotDisplay}</p>
                            <p><strong>Valor:</strong> R$ ${parseFloat(price).toFixed(2).replace('.', ',')}</p>
                            <p class="text-xs text-indigo-500 mt-1">O ID do slot fixo a ser atualizado é: #${reservaIdToUpdate}</p>
                        `;

                        // 3. Abrir o modal de agendamento rápido
                        quickBookingModal.classList.remove('hidden');

                    }
                    // --- LÓGICA DE RESERVA EXISTENTE (Modal de Detalhes) ---
                    else if (event.id) {
                        const startTime = event.start;
                        const endTime = event.end;
                        const reservaId = event.id;

                        const extendedProps = event.extendedProps || {};
                        const isRecurrent = extendedProps.is_recurrent;
                        const status = extendedProps.status;

                        const dateDisplay = moment(startTime).format('DD/MM/YYYY');

                        let timeDisplay = moment(startTime).format('H:i');
                        if (endTime) {
                            timeDisplay += ' - ' + moment(endTime).format('H:i');
                        }

                        const titleParts = event.title.split(' - R$ ');
                        const title = titleParts[0];
                        const priceDisplay = titleParts.length > 1 ? `R$ ${titleParts[1]}` : 'N/A';

                        // Determinar o status textual
                        let statusText = status;
                        if (status === 'pending') { statusText = 'Pendente'; }
                        else if (status === 'confirmed') { statusText = 'Confirmada'; }


                        const showUrl = SHOW_RESERVA_URL.replace(':id', reservaId);

                        let recurrentStatus = isRecurrent ?
                            '<p class="text-sm font-semibold text-indigo-600">Parte de uma Série Recorrente</p>' :
                            '<p class="text-sm font-semibold text-gray-500">Reserva Pontual</p>';


                        modalContent.innerHTML = `
                            <p class="font-semibold text-gray-900">${title}</p>
                            <p><strong>Status:</strong> <span class="uppercase font-bold text-sm text-${status === 'pending' ? 'orange' : 'indigo'}-600">${statusText}</span></p>
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeDisplay}</p>
                            <p><strong>Valor:</strong> <span class="text-green-600 font-bold">${priceDisplay}</span></p>
                            ${recurrentStatus}
                        `;

                        // --- LÓGICA CONDICIONAL PARA OS BOTÕES DE AÇÃO ---
                        let actionButtons = `
                            <a href="${showUrl}" class="w-full inline-block text-center mb-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150 text-sm">
                                Ver Detalhes / Gerenciar Reserva
                            </a>
                        `;

                        // ✅ ADICIONA BOTÕES DE CANCELAMENTO QUE CHAMAM O MODAL DE MOTIVO
                        if (status === 'confirmed' || status === 'pending') {
                            if (isRecurrent) {
                                actionButtons += `
                                    <button onclick="cancelarPontual(${reservaId}, true)" class="w-full mb-2 px-4 py-2 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition duration-150 text-sm">
                                        Cancelar APENAS ESTE DIA
                                    </button>
                                    <button onclick="cancelarSerie(${reservaId})" class="w-full mb-2 px-4 py-2 bg-red-800 text-white font-medium rounded-lg hover:bg-red-900 transition duration-150 text-sm">
                                        Cancelar SÉRIE INTEIRA (Futuros)
                                    </button>
                                `;
                            } else {
                                // Reserva Pontual
                                actionButtons += `
                                    <button onclick="cancelarPontual(${reservaId}, false)" class="w-full mb-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition duration-150 text-sm">
                                        Cancelar Reserva Pontual
                                    </button>
                                `;
                            }
                        }

                        actionButtons += `
                            <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150 text-sm">
                                Fechar
                            </button>
                        `;

                        modalActions.innerHTML = actionButtons;

                        eventModal.classList.remove('hidden');
                    }
                }
            });

            calendar.render();
        };
    </script>
</x-app-layout>
