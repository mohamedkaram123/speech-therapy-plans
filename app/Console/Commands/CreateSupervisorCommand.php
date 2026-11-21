<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSupervisorCommand extends Command
{
    protected $signature = 'speech:create-supervisor
        {--name= : Full name (prompted if omitted)}
        {--email= : Email (prompted if omitted)}
        {--password= : Password (prompted if omitted; avoid on shared shells)}';

    protected $description = 'Create an active supervisor account';

    public function handle(): int
    {
        $name = $this->option('name') ?: text(
            label: 'الاسم الكامل',
            required: true,
            validate: fn (string $v) => mb_strlen(trim($v)) > 255 ? 'الاسم طويل جداً' : null,
        );

        $email = $this->option('email') ?: text(
            label: 'البريد الإلكتروني',
            required: true,
            validate: fn (string $v) => $this->emailError($v),
        );

        $pass = $this->option('password') ?: password(
            label: 'كلمة المرور',
            required: true,
            validate: fn (string $v) => $this->passwordError($v),
        );

        $errors = collect([$this->emailError($email), $this->passwordError($pass)])->filter();
        if ($errors->isNotEmpty()) {
            $errors->each(fn ($e) => $this->error($e));

            return self::FAILURE;
        }

        $user = User::create([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password' => $pass, // hashed by the model cast
            'role' => UserRole::Supervisor,
            'is_active' => true,
        ]);

        $this->info("تم إنشاء المشرف: {$user->name} <{$user->email}> (#{$user->id})");

        return self::SUCCESS;
    }

    private function emailError(string $value): ?string
    {
        $v = Validator::make(
            ['email' => strtolower(trim($value))],
            ['email' => ['required', 'email:rfc', 'max:255', 'unique:users,email']],
            ['email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.', 'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.'],
        );

        return $v->fails() ? $v->errors()->first('email') : null;
    }

    private function passwordError(string $value): ?string
    {
        $v = Validator::make(
            ['password' => $value],
            ['password' => ['required', Password::min(8)]],
            ['password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.'],
        );

        return $v->fails() ? $v->errors()->first('password') : null;
    }
}
