<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_image',
        'created_by',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['profile_image_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Accessors ────────────────────────────────────────────
    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }
        // Genera avatar con iniciales como fallback
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0D8ABC&color=fff';
    }

    // ─── Helpers de Rol ───────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCaseManager(): bool
    {
        return $this->role === 'case_manager';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // ─── Relaciones ───────────────────────────────────────────

    /** Admin que creó este usuario */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Usuarios creados por este admin */
    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /** Clientes asignados a este Case Manager */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_assignments',
            'case_manager_id',
            'client_id'
        )->withTimestamps();
    }

    /** Case Managers asignados a este cliente */
    public function caseManagers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_assignments',
            'client_id',
            'case_manager_id'
        )->withTimestamps();
    }

    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = config('app.frontend_url', 'http://192.168.12.125:8000');

        ResetPasswordNotification::createUrlUsing(function ($user, $token) use ($frontendUrl) {
            return $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        // Personalizar el contenido del correo
        ResetPasswordNotification::toMailUsing(function ($notifiable, $token) use ($frontendUrl) {
            $url = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($notifiable->email);

            $isEnglish = app()->getLocale() === 'en';

            if ($isEnglish) {
                return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->subject('Reset your password')
                    ->greeting('Hello!')
                    ->line('You are receiving this email because we received a password reset request for your account.')
                    ->action('Reset Password', $url)
                    ->line('This link will expire in 60 minutes.')
                    ->line('If you did not request a password reset, no further action is required.');
            }

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Recupera tu contraseña')
                ->greeting('¡Hola!')
                ->line('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta.')
                ->action('Restablecer Contraseña', $url)
                ->line('Este enlace expirará en 60 minutos.')
                ->line('Si no solicitaste este cambio, ignora este correo.');
        });

        $this->notify(new ResetPasswordNotification($token));
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'case_manager_id');
    }
    public function clientAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }
}
