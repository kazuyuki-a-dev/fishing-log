<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[Fillable(['name', 'home_prefecture', 'notify_enabled', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
/**
 * 会員。メールアドレスを確かめるまでは、ログインが必要なページを使えない（MustVerifyEmail。#213）
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_enabled' => 'boolean',
            'is_sample' => 'boolean',
        ];
    }

    // 自分が登録した釣り場
    public function createdSpots(): HasMany
    {
        return $this->hasMany(Spot::class, 'created_by');
    }

    // 自分が現地情報を最後に更新した釣り場
    public function editedSpots(): HasMany
    {
        return $this->hasMany(Spot::class, 'updated_by');
    }

    // 自分の釣行
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    // 自分が送った報告（NF-04）
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /** 管理者か（NF-04）。role は一覧（Fillable）に入れず、画面からは変えられないようにしている */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * お試し用の専用アカウントかどうか（#136）
     * だれでもログインできるので、プロフィール・パスワードの変更と退会はさせない
     */
    /**
     * 確認のメールを送る。メール送信サービスにつながらないときは、エラーにせずログに残して false を返す（#213）
     */
    public function sendEmailVerificationSafely(): bool
    {
        try {
            $this->sendEmailVerificationNotification();

            return true;
        } catch (TransportExceptionInterface $e) {
            Log::error('確認のメールを送れませんでした', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function isDemo(): bool
    {
        return $this->email === config('fishing.demo_email');
    }
}
