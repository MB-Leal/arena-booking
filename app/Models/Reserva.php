<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    use HasFactory;

    // ------------------------------------------------------------------------
    // CONSTANTES DE STATUS
    // ------------------------------------------------------------------------
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONFIRMADA = 'confirmed';
    public const STATUS_CANCELADA = 'cancelled';
    public const STATUS_REJEITADA = 'rejected';
    public const STATUS_EXPIRADA = 'expired'; // Se o tempo de pré-reserva acabar

    /**
     * Os atributos que são mass assignable.
     * Inclui campos de cliente, agendamento e gestão.
     */
    protected $fillable = [
        'user_id',
        'schedule_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'client_name',
        'client_contact',
        'notes',
        'status',
        'manager_id',           // ID do gestor que criou/confirmou
        'is_fixed',             // Se é uma reserva fixa recorrente (CRÍTICO: Faltava na sua versão)
        'day_of_week',          // Dia da semana para reservas fixas (CRÍTICO: Faltava na sua versão)
        'recurrent_series_id',  // ID da série recorrente (se for fixa)
        'week_index',           // Índice dentro da série (se for fixa)
    ];

    /**
     * Os atributos que devem ser convertidos (casted) para tipos nativos.
     */
    protected $casts = [
        'date' => 'date',       // CORRIGIDO: Deve ser 'date' para manipular o Carbon
        'is_fixed' => 'boolean', // CRÍTICO: Conversão para booleano
    ];


    // ------------------------------------------------------------------------
    // RELACIONAMENTOS
    // ------------------------------------------------------------------------

    /**
     * Relação com o Usuário (o cliente que fez a reserva, se houver)
     */
    public function user(): BelongsTo
    {
        // Assume que o modelo User é App\Models\User
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relação com o Gestor que manipulou ou criou a reserva (se houver)
     */
    public function manager(): BelongsTo
    {
        // Usamos o modelo User para referenciar o gestor
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relação com a regra de horário (Schedule) que originou a reserva.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }


    // ------------------------------------------------------------------------
    // ACESSORES
    // ------------------------------------------------------------------------

    /**
     * Retorna o nome amigável do status (usado nas listas do Admin).
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_CONFIRMADA => 'Confirmada',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_REJEITADA => 'Rejeitada',
            self::STATUS_EXPIRADA => 'Expirada',
            default => 'Desconhecido',
        };
    }
}
