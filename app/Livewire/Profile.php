<?php

namespace App\Livewire;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Protect]
class Profile extends Component
{
    use WithFileUploads;

    public string $profileName = '';

    public string $timezone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $deletePassword = '';

    public $avatar = null;

    public bool $verificationEmailSent = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->profileName = $user->name;
        $this->timezone = $user->timezone ?? 'UTC';
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'profileName' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();
        $data = [
            'name' => $validated['profileName'],
            'timezone' => $validated['timezone'],
        ];

        if ($this->avatar) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $webpContents = (string) (new ImageManager(new ImagickDriver))
                ->decodeBinary(file_get_contents($this->avatar->getRealPath()))
                ->encode(new WebpEncoder(quality: 80));

            $path = 'avatars/'.Str::uuid().'.webp';
            Storage::disk('public')->put($path, $webpContents);
            $data['avatar'] = $path;
        }

        $user->update($data);

        session()->flash('success', 'Profile updated.');
        $this->redirect(route('profile.edit'));
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');

            return;
        }

        $user->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('success', 'Password changed.');
        $this->redirect(route('profile.edit'));
    }

    public function sendVerificationEmail(): void
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->verificationEmailSent = true;
        }
    }

    public function checkVerificationStatus(): void
    {
        $user = Auth::user();
        $user->refresh();

        if ($user->hasVerifiedEmail()) {
            $this->verificationEmailSent = false;
            session()->flash('success', 'Email verified successfully!');
            $this->redirect(route('profile.edit'));
        } else {
            $this->addError('verification', 'Email not yet verified. Please check your inbox.');
        }
    }

    public function confirmDeleteAccount(): void
    {
        $this->validate(['deletePassword' => 'required']);
        $this->dispatch('show-delete-account-confirmation');
    }

    public function deleteAccount(): void
    {
        $this->validate(['deletePassword' => 'required']);

        $user = Auth::user();

        if (! Hash::check($this->deletePassword, $user->password)) {
            $this->addError('deletePassword', 'The password is incorrect.');

            return;
        }

        Auth::logout();
        $user->delete();

        session()->flash('success', 'Account deleted.');
        $this->redirect(route('login'));
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'));
    }

    public function render(): View
    {
        return view('livewire.profile', [
            'user' => Auth::user(),
            'timezones' => collect(timezone_identifiers_list()),
        ]);
    }
}
