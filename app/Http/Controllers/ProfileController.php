<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->microsoft_id) {
            return redirect()->route('profile.edit')->with('error', 'Profile fields are managed by Microsoft 365 and cannot be edited here.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        AuditLogger::log(
            event: 'profile.update',
            action: 'updated',
            request: $request,
            userId: $user->id,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: ['fields' => ['name', 'email']]
        );

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $policy = config('security.password_policy', []);
        $passwordRule = Password::min((int) ($policy['min_length'] ?? 10));

        if ($policy['require_letters'] ?? true) {
            $passwordRule = $passwordRule->letters();
        }
        if ($policy['require_mixed_case'] ?? true) {
            $passwordRule = $passwordRule->mixedCase();
        }
        if ($policy['require_numbers'] ?? true) {
            $passwordRule = $passwordRule->numbers();
        }
        if ($policy['require_symbols'] ?? true) {
            $passwordRule = $passwordRule->symbols();
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', $passwordRule],
        ]);

        if (! Hash::check($request->input('current_password'), (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        if ($this->isPasswordReused($user, $request->input('password'))) {
            return back()->withErrors(['password' => 'Password cannot match your recent password history.'])->withInput();
        }

        $user->update([
            'password' => $request->input('password'),
            'password_changed_at' => now(),
        ]);

        $this->storePasswordHistory($user);

        AuditLogger::log(
            event: 'profile.update',
            action: 'password_changed',
            request: $request,
            userId: $user->id,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
        );

        return redirect()->route('profile.edit')->with('success', 'Password changed successfully.');
    }

    private function isPasswordReused(User $user, string $plainPassword): bool
    {
        if (! empty($user->password) && Hash::check($plainPassword, $user->password)) {
            return true;
        }

        $historyCount = (int) config('security.password_policy.history_count', 5);

        if ($historyCount <= 0) {
            return false;
        }

        $historyHashes = DB::table('user_password_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($historyCount)
            ->pluck('password');

        foreach ($historyHashes as $hash) {
            if (Hash::check($plainPassword, (string) $hash)) {
                return true;
            }
        }

        return false;
    }

    private function storePasswordHistory(User $user): void
    {
        if (empty($user->password)) {
            return;
        }

        DB::table('user_password_histories')->insert([
            'user_id' => $user->id,
            'password' => $user->password,
            'password_changed_at' => $user->password_changed_at ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $historyCount = (int) config('security.password_policy.history_count', 5);

        if ($historyCount <= 0) {
            return;
        }

        $idsToDelete = DB::table('user_password_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->skip($historyCount)
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            DB::table('user_password_histories')->whereIn('id', $idsToDelete)->delete();
        }
    }
}
