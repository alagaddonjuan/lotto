<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AuthManageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:user 
                            {email? : Email address of the user}
                            {--get-otp : View active 2FA OTP code and expiration}
                            {--disable-2fa : Immediately disable two-factor authentication}
                            {--enable-2fa : Immediately enable two-factor authentication}
                            {--test-mail : Dispatch a live test 2FA email via SMTP and show diagnostics}
                            {--set-password= : Reset the user password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Institutional identity, 2FA, and credentials administrator tool';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $users = User::with('tenant')->get();
            $headers = ['ID', 'Name', 'Email', 'Role', 'Tenant', '2FA Active', 'Current OTP', 'Expires At'];
            $rows = $users->map(fn($u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->role,
                $u->tenant?->name ?? 'Q4I Platform',
                $u->two_factor_enabled ? 'YES (Active)' : 'NO (Disabled)',
                $u->two_factor_code ?? 'None',
                $u->two_factor_expires_at?->diffForHumans() ?? 'N/A',
            ])->toArray();

            $this->info("=== Q4I Clearinghouse Operator Directory ===");
            $this->table($headers, $rows);
            return Command::SUCCESS;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email [{$email}] not found.");
            return Command::FAILURE;
        }

        if ($this->option('disable-2fa')) {
            $user->forceFill([
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ])->save();

            $this->warn("2FA has been DISABLED for [{$user->email}]. The user can now log in directly with their password.");
            return Command::SUCCESS;
        }

        if ($this->option('enable-2fa')) {
            $user->forceFill([
                'two_factor_enabled' => true,
            ])->save();

            $this->info("2FA has been ENABLED for [{$user->email}].");
            return Command::SUCCESS;
        }

        if ($newPass = $this->option('set-password')) {
            $user->forceFill([
                'password' => Hash::make($newPass),
            ])->save();

            $this->info("Password for [{$user->email}] successfully updated.");
            return Command::SUCCESS;
        }

        if ($this->option('test-mail')) {
            $this->info("=== SMTP Transport Diagnostics ===");
            $this->line("Default Mailer: " . config('mail.default'));
            $this->line("SMTP Host:      " . config('mail.mailers.smtp.host'));
            $this->line("SMTP Port:      " . config('mail.mailers.smtp.port'));
            $this->line("SMTP Scheme:    " . config('mail.mailers.smtp.scheme'));
            $this->line("SMTP Username:  " . (config('mail.mailers.smtp.username') ?: '(empty)'));
            $this->line("From Address:   " . config('mail.from.address'));

            $otp = $user->generateTwoFactorCode();
            try {
                $user->notify(new \App\Notifications\TwoFactorCodeNotification($otp));
                $this->info("SUCCESS: 2FA verification email (Code: {$otp}) dispatched to [{$user->email}] via [" . config('mail.default') . "] mailer.");
                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $this->error("SMTP ERROR: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Default detail view
        $this->info("=== User Identity & 2FA Status ===");
        $this->line("Name:        {$user->name}");
        $this->line("Email:       {$user->email}");
        $this->line("Role:        {$user->role}");
        $this->line("2FA Active:  " . ($user->two_factor_enabled ? 'YES' : 'NO'));
        $this->line("Active OTP:  " . ($user->two_factor_code ?? 'None (No challenge pending)'));
        $this->line("Expires:     " . ($user->two_factor_expires_at ? $user->two_factor_expires_at->toDateTimeString() . ' (' . $user->two_factor_expires_at->diffForHumans() . ')' : 'N/A'));

        return Command::SUCCESS;
    }
}

