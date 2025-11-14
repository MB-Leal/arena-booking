<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configuração de Horários Recorrentes da Arena') }}
        </h2>
    </x-slot>

    <style>
        /* Estilos CSS existentes */
        .fixed-reserva-status-btn {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .status-confirmed {
            background-color: #d1fae5; /* Green 100 */
            color: #065f46; /* Green 900 */
        }
        .status-cancelled {
            background-color: #fee2e2; /* Red 100 */
            color: #991b1b; /* Red 900 */
        }
        .price-input {
            width: 80px;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .icon-save, .icon-edit {
            cursor: pointer;
            margin-left: 8px;
        }
        .slot-container {
            border: 1px solid #e5e7eb; /* Gray 200 */
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background-color: #fafafa; /* Gray 50 */
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Notificações (MANTIDAS) --}}
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
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                    <p>Houve um erro na validação dos dados. Por favor, verifique os campos e tente novamente.</p>
                </div>
            @endif


            {{-- Formulário de Configuração Semanal (MÚLTIPLOS SLOTS) --}}
            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg mb-8">
                <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Horários de Funcionamento Recorrente (Múltiplas Faixas de Preço)
                    </h3>

                    {{-- ✅ NOVO: MENSAGEM DE PROCESSO AUTOMÁTICO --}}
                    <div class="mt-4 p-4 bg-blue-100 border border-blue-400 rounded-lg dark:bg-blue-900 dark:border-blue-700 mb-6">
                        <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            ✅ Processo Automático: As reservas fixas (slots disponíveis) são agora **geradas automaticamente** para o próximo ano, logo após você clicar em "Salvar Configuração Semanal".
                        </p>
                    </div>


                    <form id="config-form" action="{{ route('admin.config.store') }}" method="POST">
                        @csrf
                        <div class="space-y-6">
                            @php
                                $dayConfigurations = $dayConfigurations ?? [];
                            @endphp

                            @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
                                @php
                                    $slots = $dayConfigurations[$dayOfWeek] ?? [];
                                    $hasSlots = !empty($slots);

                                    $isDayActive = $hasSlots ? collect($slots)->contains('is_active', true) : false;

                                    // Corrige o placeholder para ser um array associativo
                                    if (!$hasSlots)
                                    {
                                        $slots[] = ['start_time' => '06:00:00', 'end_time' => '23:00:00', 'default_price' => 100.00, 'is_active' => false];
                                    }
                                @endphp

                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow-inner">
                                    <div class="flex items-center space-x-4 mb-4 border-b pb-2">
                                        {{-- Checkbox Mestre de Ativo/Inativo para o dia --}}
                                        <input type="checkbox" name="day_status[{{ $dayOfWeek }}]"
                                               id="day-active-{{ $dayOfWeek }}" value="1"
                                               {{ $isDayActive ? 'checked' : '' }}
                                               class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 day-toggle-master">
                                        <label for="day-active-{{ $dayOfWeek }}" class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $dayName }}
                                        </label>
                                    </div>

                                    {{-- Container para as faixas de preço --}}
                                    <div id="slots-container-{{ $dayOfWeek }}" class="slots-container mt-2"
                                         style="{{ !$isDayActive ? 'display: none;' : '' }}">

                                        @foreach ($slots as $index => $slot)
                                            {{-- Renderiza o Slot Salvo ou o Slot de Placeholder --}}
                                            <div class="slot-item slot-container flex items-center space-x-4 p-3 bg-white dark:bg-gray-600"
                                                 data-day="{{ $dayOfWeek }}" data-index="{{ $index }}">
                                                <input type="hidden" name="configs[{{ $dayOfWeek }}][{{ $index }}][day_of_week]" value="{{ $dayOfWeek }}">

                                                {{-- Checkbox de Slot Ativo --}}
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="configs[{{ $dayOfWeek }}][{{ $index }}][is_active]"
                                                           id="slot-active-{{ $dayOfWeek }}-{{ $index }}" value="1"
                                                           {{ (isset($slot['is_active']) && $slot['is_active']) ? 'checked' : '' }}
                                                           class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 slot-active-checkbox"
                                                           {{ !$isDayActive ? 'disabled' : '' }}>
                                                    <label for="slot-active-{{ $dayOfWeek }}-{{ $index }}" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Ativo
                                                    </label>
                                                </div>

                                                {{-- Horário de Início --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Início</label>
                                                    <input type="time" name="configs[{{ $dayOfWeek }}][{{ $index }}][start_time]"
                                                           value="{{ old("configs.$dayOfWeek.$index.start_time", \Carbon\Carbon::parse($slot['start_time'])->format('H:i')) }}"
                                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input"
                                                           {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.start_time")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Horário de Fim --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Fim</label>
                                                    <input type="time" name="configs[{{ $dayOfWeek }}][{{ $index }}][end_time]"
                                                           value="{{ old("configs.$dayOfWeek.$index.end_time", \Carbon\Carbon::parse($slot['end_time'])->format('H:i')) }}"
                                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input"
                                                           {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.end_time")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Preço Padrão --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Preço (R$)</label>
                                                    <input type="number" step="0.01" name="configs[{{ $dayOfWeek }}][{{ $index }}][default_price]"
                                                           value="{{ old("configs.$dayOfWeek.$index.default_price", $slot['default_price']) }}"
                                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white price-input-config"
                                                           {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.default_price")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Botão de Remover Slot --}}
                                                <div class="w-1/12">
                                                    <button type="button" class="text-red-600 hover:text-red-900 remove-slot-btn"
                                                            title="Remover Faixa de Horário"
                                                            data-day="{{ $dayOfWeek }}" {{ count($slots) === 1 ? 'disabled' : '' }}
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>

                                    {{-- Botão Adicionar Faixa --}}
                                    <div class="mt-3">
                                        <button type="button" class="inline-flex items-center px-3 py-1 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 add-slot-btn"
                                                data-day="{{ $dayOfWeek }}"
                                                {{ !$isDayActive ? 'disabled' : '' }}>
                                            + Adicionar Faixa de Horário
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- ✅ ÚNICO BOTÃO DE SUBMISSÃO (MUITO MAIS SIMPLES) --}}
                        <div class="flex justify-start mt-8">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Salvar Configuração Semanal
                            </button>
                            {{-- 🛑 O BOTÃO MANUAL FOI REMOVIDO DAQUI --}}
                        </div>
                    </form>
                </div>
            </div>

            {{-- ... Tabela de Gerenciamento de Reservas Fixas Geradas (MANTIDA) ... --}}
             <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg">
                 <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                     <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Gerenciar Horários Recorrentes Gerados (Próximas Reservas Fixas)</h3>
                     <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Esta lista é atualizada automaticamente ao salvar a configuração. Filtre a data no seu admin padrão se a lista for muito longa.</p>

                     <div class="overflow-x-auto">
                         <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                             <thead>
                                 <tr>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horário</th>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome (Série)</th>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço (R$)</th>
                                     <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                 </tr>
                             </thead>
                             <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                 @forelse ($fixedReservas as $reserva)
                                     <tr id="row-{{ $reserva->id }}">
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $reserva->id }}</td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                             {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                         </td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $reserva->client_name }}</td>

                                         {{-- Preço Editável --}}
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 flex items-center">
                                             <span id="price-display-{{ $reserva->id }}"
                                                   class="font-semibold text-indigo-600 dark:text-indigo-400">
                                                 {{ number_format($reserva->price, 2, ',', '.') }}
                                             </span>
                                             <input type="number" step="0.01" id="price-input-{{ $reserva->id }}"
                                                     value="{{ $reserva->price }}"
                                                     class="price-input hidden" data-id="{{ $reserva->id }}">

                                             <span class="icon-edit" id="edit-icon-{{ $reserva->id }}"
                                                     data-id="{{ $reserva->id }}"
                                                     onclick="toggleEdit({{ $reserva->id }}, true)">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                             </span>

                                             <span class="icon-save hidden" id="save-icon-{{ $reserva->id }}"
                                                     data-id="{{ $reserva->id }}"
                                                     onclick="updatePrice({{ $reserva->id }})">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 hover:text-green-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                             </span>
                                         </td>

                                         {{-- Status/Ações --}}
                                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                             <button id="status-btn-{{ $reserva->id }}"
                                                     class="fixed-reserva-status-btn {{ $reserva->status === 'confirmed' ? 'status-confirmed' : 'status-cancelled' }}"
                                                     data-id="{{ $reserva->id }}"
                                                     data-current-status="{{ $reserva->status }}"
                                                     onclick="toggleStatus({{ $reserva->id }})">
                                                 {{ $reserva->status === 'confirmed' ? 'Disponível' : 'Indisponível (Manutenção)' }}
                                             </button>
                                         </td>
                                     </tr>
                                 @empty
                                     <tr>
                                         <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Nenhuma reserva fixa gerada. Configure os horários acima e salve.</td>
                                     </tr>
                                 @endforelse
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
        </div>
    </div>

    <script>
        // TOKEN CSRF NECESSÁRIO PARA REQUISIÇÕES AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Contadores para garantir índices únicos ao adicionar novos slots
        const nextIndex = {};

        // Inicializa contadores de índice
        @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
            nextIndex[{{ $dayOfWeek }}] = document.querySelectorAll('#slots-container-{{ $dayOfWeek }} .slot-item').length;
            if (nextIndex[{{ $dayOfWeek }}] === 0) {
                 nextIndex[{{ $dayOfWeek }}] = 1; // Garante que o primeiro slot adicionado seja o 1
            }
        @endforeach


        function updateRemoveButtonState(dayOfWeek) {
            const container = document.getElementById(`slots-container-${dayOfWeek}`);
            const removeButtons = container.querySelectorAll('.remove-slot-btn');
            const numSlots = container.querySelectorAll('.slot-item').length;

            // Desabilita o botão de remover se houver apenas 1 slot
            removeButtons.forEach(btn => {
                btn.disabled = numSlots <= 1;
            });
        }

        function updateSlotInputsState(dayOfWeek, isDisabled) {
            const container = document.getElementById(`slots-container-${dayOfWeek}`);
            const inputs = container.querySelectorAll('input[type="time"], input[type="number"], .slot-active-checkbox');
            const addBtn = document.querySelector(`.add-slot-btn[data-day="${dayOfWeek}"]`);

            inputs.forEach(input => {
                input.disabled = isDisabled;
            });

            // Desabilita/habilita botões de remover/adicionar
            container.querySelectorAll('.remove-slot-btn').forEach(btn => {
                btn.disabled = isDisabled || (container.querySelectorAll('.slot-item').length <= 1);
            });
            if (addBtn) addBtn.disabled = isDisabled;
        }

        // --- LÓGICA DE GERENCIAMENTO DE SLOTS (JS) ---

        // 1. Alternância do Dia Mestre
        document.querySelectorAll('.day-toggle-master').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.id.replace('day-active-', '');
                const isDisabled = !this.checked;
                const container = document.getElementById(`slots-container-${day}`);

                if (!isDisabled) {
                    container.style.display = 'block';
                    // Garante que o checkbox do primeiro slot fica ativo quando o mestre é ativado
                    const firstSlotCheckbox = container.querySelector('.slot-active-checkbox');
                    if (firstSlotCheckbox) firstSlotCheckbox.checked = true;
                } else {
                    container.style.display = 'none';
                    // Desativa todos os slots (embora o Controller só considere os que têm o master ativado)
                    container.querySelectorAll('.slot-active-checkbox').forEach(cb => cb.checked = false);
                }

                updateSlotInputsState(day, isDisabled);
                updateRemoveButtonState(day);
            });
        });

        // 2. Adicionar Slot
        document.querySelectorAll('.add-slot-btn').forEach(button => {
            button.addEventListener('click', function() {
                const dayOfWeek = this.dataset.day;
                const container = document.getElementById(`slots-container-${dayOfWeek}`);
                const index = nextIndex[dayOfWeek];

                // Cópia do HTML de um slot de placeholder
                const newSlotHtml = `
                    <div class="slot-item slot-container flex items-center space-x-4 p-3 bg-white dark:bg-gray-600" data-day="${dayOfWeek}" data-index="${index}">
                        <input type="hidden" name="configs[${dayOfWeek}][${index}][day_of_week]" value="${dayOfWeek}">

                        <div class="flex items-center">
                            <input type="checkbox" name="configs[${dayOfWeek}][${index}][is_active]"
                                    id="slot-active-${dayOfWeek}-${index}" value="1" checked
                                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 slot-active-checkbox">
                            <label for="slot-active-${dayOfWeek}-${index}" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ativo
                            </label>
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Início</label>
                            <input type="time" name="configs[${dayOfWeek}][${index}][start_time]" value="08:00"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input">
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Fim</label>
                            <input type="time" name="configs[${dayOfWeek}][${index}][end_time]" value="12:00"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input">
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Preço (R$)</label>
                            <input type="number" step="0.01" name="configs[${dayOfWeek}][${index}][default_price]" value="120.00"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white price-input-config">
                        </div>

                        <div class="w-1/12">
                            <button type="button" class="text-red-600 hover:text-red-900 remove-slot-btn" title="Remover Faixa de Horário" data-day="${dayOfWeek}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', newSlotHtml);
                nextIndex[dayOfWeek]++;

                // Reatribui listener de remoção
                attachRemoveListeners();
                updateRemoveButtonState(dayOfWeek);
            });
        });

        // 3. Remover Slot (Função auxiliar para reatribuir)
        function attachRemoveListeners() {
            document.querySelectorAll('.remove-slot-btn').forEach(button => {
                // Remove o listener anterior para evitar duplicidade
                button.removeEventListener('click', handleRemoveClick);
                // Adiciona o novo listener
                button.addEventListener('click', handleRemoveClick);
            });
        }

        function handleRemoveClick() {
            const slotItem = this.closest('.slot-item');
            const dayOfWeek = this.dataset.day;
            if (slotItem) {
                slotItem.remove();
                updateRemoveButtonState(dayOfWeek);
            }
        }

        // Inicializa listeners de remoção no carregamento
        attachRemoveListeners();

        // Inicializa o estado dos inputs e botões (no carregamento da página)
        document.addEventListener('DOMContentLoaded', function() {
            // Garante que o estado inicial dos botões de remoção está correto
            @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
                updateRemoveButtonState({{ $dayOfWeek }});
            @endforeach
        });

        // Lógica de Edição de Preço e Status (Tabela de Reservas Fixas) - Mantida
        // ... (toggleEdit, updatePrice, toggleStatus) ...

        function toggleEdit(id, isEditing) {
            const display = document.getElementById(`price-display-${id}`);
            const input = document.getElementById(`price-input-${id}`);
            const editIcon = document.getElementById(`edit-icon-${id}`);
            const saveIcon = document.getElementById(`save-icon-${id}`);

            if (isEditing) {
                display.classList.add('hidden');
                editIcon.classList.add('hidden');
                input.classList.remove('hidden');
                saveIcon.classList.remove('hidden');
                input.focus();
            } else {
                display.classList.remove('hidden');
                editIcon.classList.remove('hidden');
                input.classList.add('hidden');
                saveIcon.classList.add('hidden');
            }
        }

        async function updatePrice(id) {
            const input = document.getElementById(`price-input-${id}`);
            const newPrice = parseFloat(input.value);

            if (isNaN(newPrice) || newPrice < 0) {
                alert('Preço inválido.');
                return;
            }

            try {
                const response = await fetch(`/admin/config/fixed-reserva/${id}/price`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ price: newPrice })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    document.getElementById(`price-display-${id}`).textContent = newPrice.toFixed(2).replace('.', ',');
                    alert(result.message);
                    toggleEdit(id, false);
                } else {
                    alert('Erro ao atualizar preço: ' + (result.error || result.message));
                }
            } catch (error) {
                console.error('Erro de rede ao atualizar preço:', error);
                alert('Erro de conexão com o servidor.');
            }
        }

        async function toggleStatus(id) {
            const button = document.getElementById(`status-btn-${id}`);
            const currentStatus = button.getAttribute('data-current-status');
            const newStatus = currentStatus === 'confirmed' ? 'cancelled' : 'confirmed';

            button.disabled = true;
            button.textContent = 'Aguardando...';

            try {
                const response = await fetch(`/admin/config/fixed-reserva/${id}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    button.setAttribute('data-current-status', newStatus);

                    if (newStatus === 'confirmed') {
                        button.textContent = 'Disponível';
                        button.classList.remove('status-cancelled');
                        button.classList.add('status-confirmed');
                    } else {
                        button.textContent = 'Indisponível (Manutenção)';
                        button.classList.remove('status-confirmed');
                        button.classList.add('status-cancelled');
                    }
                    alert(result.message);
                } else {
                    alert('Erro ao atualizar status: ' + (result.error || result.message));
                }

            } catch (error) {
                console.error('Erro de rede ao atualizar status:', error);
                alert('Erro de conexão com o servidor.');
            } finally {
                button.disabled = false;
            }
        }
    </script>
</x-app-layout>
