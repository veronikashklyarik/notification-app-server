<?php

namespace App\Livewire;

use App\Notifications\WebEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
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

    public function updatedAvatar(): void
    {
        if (! $this->avatar) {
            return;
        }

        $this->validateOnly('avatar', ['avatar' => 'extensions:jpg,jpeg,png,heic,heif,webp|max:10240']);

        $user = Auth::user();

        $tempJpeg = null;

        try {
            $jpegPath = $this->convertToJpeg($this->avatar->getRealPath(), $this->avatar->getClientOriginalExtension());
            $tempJpeg = $jpegPath;

            $webpContents = (string) (new ImageManager(new GdDriver))
                ->decodeBinary(file_get_contents($jpegPath))
                ->encode(new WebpEncoder(quality: 80));

            $path = 'avatars/'.Str::uuid().'.webp';
            Storage::disk('public')->put($path, $webpContents);

            $oldAvatar = $user->avatar;
            $user->update(['avatar' => $path]);

            if ($oldAvatar) {
                Storage::disk('public')->delete($oldAvatar);
            }
        } catch (\Throwable $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->avatar = null;
            $this->addError('avatar', 'Failed to upload photo. Please try again.');

            return;
        } finally {
            if ($tempJpeg && file_exists($tempJpeg)) {
                @unlink($tempJpeg);
            }
        }

        $this->avatar = null;
        session()->flash('success', 'Profile photo updated.');
        $this->redirect(route('profile.edit'));
    }

    private function convertToJpeg(string $sourcePath, string $extension): string
    {
        $outputPath = sys_get_temp_dir().'/'.Str::uuid().'.jpg';
        $extension = strtolower($extension);

        // Use sips on macOS — handles HEIC, PNG, WebP, JPEG, etc.
        if (PHP_OS_FAMILY === 'Darwin') {
            exec('sips -s format jpeg '.escapeshellarg($sourcePath).' --out '.escapeshellarg($outputPath).' 2>/dev/null', output: $_, result_code: $code);

            if ($code === 0 && file_exists($outputPath)) {
                return $outputPath;
            }
        }

        // Use ImageMagick CLI on Linux/cross-platform
        exec('convert '.escapeshellarg($sourcePath).' '.escapeshellarg($outputPath).' 2>/dev/null', output: $_, result_code: $code);

        if ($code === 0 && file_exists($outputPath)) {
            return $outputPath;
        }

        // Fall back to Intervention Image for standard formats (jpeg, png, webp)
        if (! in_array($extension, ['heic', 'heif'])) {
            $jpegContents = (string) (new ImageManager(new GdDriver))
                ->decodeBinary(file_get_contents($sourcePath))
                ->encode(new JpegEncoder(quality: 95));

            file_put_contents($outputPath, $jpegContents);

            return $outputPath;
        }

        throw new \RuntimeException('HEIC conversion is not supported on this server. Please upload a JPEG or PNG instead.');
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'profileName' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
        ]);

        $user = Auth::user();

        $user->update([
            'name' => $validated['profileName'],
            'timezone' => $validated['timezone'],
        ]);

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
            $user->notify(new WebEmailVerificationNotification);
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
