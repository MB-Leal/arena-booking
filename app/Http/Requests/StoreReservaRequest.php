<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreReservaRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer este request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam ao request.
     */
    public function rules(): array
    {
        // Define a data mínima como "hoje"
        $minDate = Carbon::now()->format('Y-m-d');

        return [
            // Campos do Cliente
            'nome_cliente'      => ['required', 'string', 'max:255'],

            // ✅ CORREÇÃO CRÍTICA: Aplica regex para aceitar apenas 10 ou 11 dígitos numéricos.
            'contato_cliente'   => ['required', 'string', 'regex:/^\d{10,11}$/'],

            // Campos de Horário
            'data_reserva'      => ['required', 'date', "after_or_equal:{$minDate}"],
            'hora_inicio'       => ['required', 'date_format:H:i'],
            'hora_fim'          => ['required', 'date_format:H:i', 'after:hora_inicio'],

            // Campos Hidden (usados para passar dados de volta)
            'price'             => ['required', 'numeric', 'min:0'],
            'schedule_id'       => ['required', 'integer', 'exists:schedules,id'],
            'is_fixed'          => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Personaliza as mensagens de erro.
     */
    public function messages(): array
    {
        return [
            // Mensagens sincronizadas com os novos nomes de campo
            'data_reserva.required' => 'A data da reserva é obrigatória.',
            'data_reserva.after_or_equal' => 'Não é possível agendar em datas passadas.',
            'hora_inicio.required' => 'O horário de início é obrigatório.',
            'hora_fim.required' => 'O horário de fim é obrigatório.',
            'hora_fim.after' => 'O horário de término deve ser após o horário de início.',

            'nome_cliente.required' => 'O nome completo do cliente é obrigatório.',
            'contato_cliente.required' => 'O contato (WhatsApp) é obrigatório.',

            // ✅ MENSAGEM CRÍTICA PARA A CORREÇÃO:
            'contato_cliente.regex' => 'O WhatsApp deve conter 10 ou 11 dígitos (apenas números, incluindo o DDD).',

            // Mensagens para campos hidden, se estiverem faltando
            'price.required' => 'O valor da reserva não foi selecionado.',
            'schedule_id.required' => 'O horário selecionado é inválido.',
        ];
    }
}
