<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // Adicionado para uso do helper str_contains (se necessário, mas o Laravel já deve carregar)

class ConfigurationController extends Controller
{
    /**
     * Exibe o formulário de configuração e a lista de reservas fixas.
     */
    public function index()
    {
        // 1. Recupera todas as configurações do banco, agrupadas pelo dia da semana (0-6)
        $configs = ArenaConfiguration::all()->keyBy('day_of_week');

        // 2. Transforma o resultado para o formato esperado pela View
        $dayConfigurations = [];
        foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
            $config = $configs->get($dayOfWeek);
            if ($config && !empty($config->config_data)) {
                $dayConfigurations[$dayOfWeek] = $config->config_data;
            } else {
                $dayConfigurations[$dayOfWeek] = [];
            }
        }

        // 3. Obtém as próximas 50 Reservas Fixas para exibição na tabela (usando is_fixed=true)
        $fixedReservas = Reserva::where('is_fixed', true)
            ->where('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get();

        return view('admin.config.index', [
            'dayConfigurations' => $dayConfigurations,
            'fixedReservas' => $fixedReservas,
        ]);
    }

    /**
     * Salva a configuração semanal (agora com múltiplos slots/faixas de preço)
     * e dispara a geração automática de reservas fixas.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'day_status.*' => 'nullable|boolean',
            'configs' => 'nullable|array',
            'configs.*' => 'nullable|array',
        ]);

        $rulesForSlots = [
            'configs.*.*.day_of_week' => 'nullable|integer|min:0|max:6',
            'configs.*.*.is_active' => 'nullable|boolean',
            'configs.*.*.start_time' => 'required_with:configs.*.*.default_price|date_format:H:i',
            'configs.*.*.end_time' => 'required_with:configs.*.*.start_time|date_format:H:i|after:configs.*.*.start_time',
            'configs.*.*.default_price' => 'required_with:configs.*.*.start_time|numeric|min:0',
        ];

        $validator->setRules(array_merge($validator->getRules(), $rulesForSlots));

        // 🛑 Validação customizada para checar sobreposição de faixas de horário no mesmo dia
        $validator->after(function ($validator) {
            // Se já houver erros de validação básica, não executa este loop complexo
            if ($validator->errors()->count()) {
                return;
            }

            $configsByDay = $validator->validated()['configs'] ?? [];

            foreach ($configsByDay as $dayOfWeek => $slots) {
                // Filtra apenas os slots que estão ativos e possuem dados válidos (conforme validação básica)
                $activeSlots = collect($slots)->filter(function ($slot) {
                    return isset($slot['is_active']) && (bool)$slot['is_active'] &&
                           !empty($slot['start_time']) && !empty($slot['end_time']);
                })->values();

                $count = $activeSlots->count();
                if ($count < 2) continue;

                // Compara cada slot com todos os outros subsequentes
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $slotA = $activeSlots->get($i);
                        $slotB = $activeSlots->get($j);

                        // Cria objetos Carbon para comparação
                        $startA = Carbon::createFromFormat('H:i', $slotA['start_time']);
                        $endA = Carbon::createFromFormat('H:i', $slotA['end_time']);
                        $startB = Carbon::createFromFormat('H:i', $slotB['start_time']);
                        $endB = Carbon::createFromFormat('H:i', $slotB['end_time']);

                        // Checa a condição de sobreposição: (A_start < B_end) AND (B_start < A_end)
                        if ($startA->lt($endB) && $startB->lt($endA)) {
                            $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

                            $errorMsg = "As faixas de horário ({$slotA['start_time']} - {$slotA['end_time']}) e ({$slotB['start_time']} - {$slotB['end_time']}) se **sobrepõem** no {$dayName}. Por favor, corrija.";

                            // Adiciona o erro ao validador, referenciando o array do dia.
                            $validator->errors()->add("configs.{$dayOfWeek}", $errorMsg);
                            return;
                        }
                    }
                }
            }
        });

        try {
            $validated = $validator->validate();
        } catch (ValidationException $e) {
            Log::error('[ERRO DE VALIDAÇÃO NA CONFIGURAÇÃO DE HORÁRIOS]', ['erros' => $e->errors(), 'input' => $request->all()]);

            // 🛑 CORREÇÃO AQUI: Garante que estamos usando o objeto MessageBag do validador.
            $messageBag = $e->validator->errors();
            $genericError = false;
            $customOverlapError = null;

            foreach ($messageBag->keys() as $key) {
                if (str_starts_with($key, 'configs.')) {
                    // Captura a mensagem de erro de sobreposição (se existir)
                    // Usamos str_contains, pois a mensagem é customizada
                    if (str_contains($messageBag->first($key), 'sobrepõem')) {
                        $customOverlapError = $messageBag->first($key);
                    }
                    $genericError = true;
                }
            }

            // Se houver um erro de sobreposição customizado, exibe-o diretamente
            if ($customOverlapError) {
                return redirect()->back()->withInput()->with('error', 'ERRO DE CONFLITO: ' . $customOverlapError);
            }

            // Se for erro de validação básica (required, after, etc.)
            if ($genericError) {
                return redirect()->back()->withInput()->with('error', 'Houve um erro na validação dos dados. Verifique se todos os campos (Início, Fim, Preço) estão preenchidos para os dias ativos, ou se o Horário de Fim é posterior ao de Início.');
            }
            return redirect()->back()->withInput()->withErrors($e->errors())->with('error', 'Erro desconhecido na validação. Verifique os logs.');
        }

        $dayStatus = $validated['day_status'] ?? [];
        $configsByDay = $validated['configs'] ?? [];

        DB::beginTransaction();
        try {
            foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
                $slotsForDay = $configsByDay[$dayOfWeek] ?? [];

                $activeSlots = collect($slotsForDay)
                    ->filter(function ($slot) {
                        $isActive = isset($slot['is_active']) && (bool)$slot['is_active'];
                        $hasData = !empty($slot['start_time']) && !empty($slot['end_time']) && !empty($slot['default_price']);
                        return $isActive && $hasData;
                    })
                    ->map(function ($slot) {
                        unset($slot['is_active']);
                        return $slot;
                    })
                    ->values()
                    ->toArray();

                $isDayActive = isset($dayStatus[$dayOfWeek]) && (bool)$dayStatus[$dayOfWeek];
                $finalIsActive = $isDayActive && !empty($activeSlots);

                $config = \App\Models\ArenaConfiguration::firstOrNew(['day_of_week' => $dayOfWeek]);

                $config->is_active = $finalIsActive;
                $config->config_data = $finalIsActive ? $activeSlots : [];

                $config->save();
            }

            DB::commit();

            $generateResult = $this->generateFixedReservas(new Request());

            return $generateResult;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao salvar configuração: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro ao salvar a configuração: ' . $e->getMessage());
        }
    }

    /**
     * Limpa e Recria TODAS as FixedReservas com base na ArenaConfiguration.
     */
    public function generateFixedReservas(Request $request)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addYear();

        // Limpa todas as FixedReservas futuras
        Reserva::where('is_fixed', true)
            ->where('date', '>=', $today->toDateString())
            ->delete();

        $activeConfigs = ArenaConfiguration::where('is_active', true)->get();
        $newReservasCount = 0;

        DB::beginTransaction();
        try {
            for ($date = $today->copy(); $date->lessThan($endDate); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeek;

                $config = $activeConfigs->firstWhere('day_of_week', $dayOfWeek);

                if ($config && $config->is_active && !empty($config->config_data)) {

                    foreach ($config->config_data as $slot) {
                        $startTime = Carbon::parse($slot['start_time']);
                        $endTime = Carbon::parse($slot['end_time']);
                        $price = $slot['default_price'];

                        $currentSlotTime = $startTime->copy();
                        while ($currentSlotTime->lessThan($endTime)) {
                            $nextSlotTime = $currentSlotTime->copy()->addHour();

                            if ($nextSlotTime->greaterThan($endTime)) {
                                break;
                            }

                            Reserva::create([
                                'date' => $date->toDateString(),
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $currentSlotTime->format('H:i:s'),
                                'end_time' => $nextSlotTime->format('H:i:s'),
                                'price' => $price,
                                'client_name' => 'Slot Fixo de 1h',
                                'client_contact' => 'N/A',
                                'status' => 'confirmed',
                                'is_fixed' => true,
                            ]);
                            $newReservasCount++;

                            $currentSlotTime->addHour();
                        }
                    }
                }
            }
            DB::commit();

            return redirect()->route('admin.config.index')->with('success', "Configuração salva e **{$newReservasCount} reservas fixas** geradas com sucesso para o próximo ano. O processo agora é automático após o salvamento.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro na geração de reservas fixas: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro na geração de reservas fixas: ' . $e->getMessage());
        }
    }


    /**
     * Métodos de gerenciamento (updateFixedReservaPrice e toggleFixedReservaStatus)
     */
    public function updateFixedReservaPrice(Request $request, $id)
    {
        $request->validate(['price' => 'required|numeric|min:0']);

        $reserva = Reserva::where('is_fixed', true)->find($id);

        if (!$reserva) {
            return response()->json(['success' => false, 'error' => 'Reserva fixa não encontrada.'], 404);
        }

        $reserva->price = $request->price;
        $reserva->save();

        return response()->json(['success' => true, 'message' => 'Preço atualizado com sucesso.']);
    }

    public function toggleFixedReservaStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:confirmed,cancelled']);

        $reserva = Reserva::where('is_fixed', true)->find($id);

        if (!$reserva) {
            return response()->json(['success' => false, 'error' => 'Reserva fixa não encontrada.'], 404);
        }

        $reserva->status = $request->status;
        $reserva->save();

        $action = $request->status === 'confirmed' ? 'disponibilizado' : 'marcado como indisponível';

        return response()->json(['success' => true, 'message' => "Slot $action com sucesso."]);
    }
}
