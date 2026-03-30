<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task',
        'description',
        'is_completed',
        'is_recurring',
        'recurring_days',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TodoCompletion::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(TodoShare::class);
    }

    public function sharedWithUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'todo_shares',
            'todo_id',
            'shared_with_user_id'
        );
    }

    public function isCompletedTodayByUser(int $userId): bool
    {
        return $this->completions()
            ->where('user_id', $userId)
            ->whereDate('completed_at', today())
            ->exists();
    }

    public function lastSevenDays(int $userId): array
    {
        return collect(range(6, 0))->map(function ($i) use ($userId) {
            $date = today()->subDays($i);
            $done = $this->completions()
                ->where('user_id', $userId)
                ->whereDate('completed_at', $date)
                ->exists();
            return [
                'date'  => $date->toDateString(),
                'label' => $date->locale('pt')->isoFormat('ddd'),
                'done'  => $done,
                'today' => $date->isToday(),
            ];
        })->toArray();
    }

    // Histórico completo agrupado por mês
    public function fullHistory(int $userId): array
    {
        return $this->completions()
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->get()
            ->groupBy(fn($c) => $c->completed_at->format('Y-m'))
            ->map(fn($group, $month) => [
                'mes'   => \Carbon\Carbon::parse($month . '-01')->locale('pt')->isoFormat('MMMM YYYY'),
                'total' => $group->count(),
                'dias'  => $group->map(fn($c) => $c->completed_at->format('d'))->toArray(),
            ])
            ->values()
            ->toArray();
    }

    public function getRecurringDaysArray(): array
    {
        if (!$this->recurring_days) return [];
        return array_map('intval', explode(',', $this->recurring_days));
    }

    // Estatísticas
    public function statsForUser(int $userId): array
    {
        $totalFeito = $this->completions()->where('user_id', $userId)->count();

        $estaSemana = $this->completions()
            ->where('user_id', $userId)
            ->whereBetween('completed_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count();

        $esteMes = $this->completions()
            ->where('user_id', $userId)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        return [
            'total'       => $totalFeito,
            'esta_semana' => $estaSemana,
            'este_mes'    => $esteMes,
        ];
    }
}
