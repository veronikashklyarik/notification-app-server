<?php

namespace App\Livewire;

use App\Notifications\WebEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    public string $profileName = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $deletePassword = '';

    public bool $confirmingDeleteAccount = false;

    public bool $editingEmail = false;

    public string $newEmail = '';

    public string $emailChangePassword = '';

    public $avatar = null;

    public ?int $verificationSentAt = null;

    public bool $nameJustSaved = false;

    public function mount(): void
    {
        $this->profileName = Auth::user()->name;
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
            $this->addError('avatar', __('Failed to upload photo. Please try again.'));

            return;
        } finally {
            if ($tempJpeg && file_exists($tempJpeg)) {
                @unlink($tempJpeg);
            }
        }

        $this->avatar = null;
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
        ]);

        Auth::user()->update([
            'name' => $validated['profileName'],
        ]);

        $this->nameJustSaved = true;
    }

    public function changePassword(): void
    {
        $user = Auth::user();

        if ($user->password) {
            $this->validate([
                'current_password' => 'required',
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ]);

            if (! Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', __('The current password is incorrect.'));

                return;
            }
        } else {
            $this->validate([
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ]);
        }

        $user->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('success', __('Password changed.'));
        $this->redirect(route('profile.edit'));
    }

    public function startEditingEmail(): void
    {
        $this->editingEmail = true;
        $this->newEmail = '';
        $this->emailChangePassword = '';
        $this->resetErrorBag(['newEmail', 'emailChangePassword']);
    }

    public function cancelEditingEmail(): void
    {
        $this->editingEmail = false;
        $this->newEmail = '';
        $this->emailChangePassword = '';
        $this->resetErrorBag(['newEmail', 'emailChangePassword']);
    }

    public function changeEmail(): void
    {
        $user = Auth::user();

        $this->validate([
            'newEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($user->password) {
            $this->validate(['emailChangePassword' => ['required']]);

            if (! Hash::check($this->emailChangePassword, $user->password)) {
                $this->addError('emailChangePassword', __('The password is incorrect.'));

                return;
            }
        }

        $user->forceFill([
            'email' => $this->newEmail,
            'email_verified_at' => null,
        ])->save();

        $user->notify(new WebEmailVerificationNotification);

        $this->editingEmail = false;
        $this->newEmail = '';
        $this->emailChangePassword = '';
        $this->verificationSentAt = null;

        session()->flash('success', __('Email updated. Check your inbox to verify it.'));
    }

    public function sendVerificationEmail(): void
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->notify(new WebEmailVerificationNotification);
            $this->verificationSentAt = now()->timestamp;
        }
    }

    /**
     * Silently polled while the email is unverified — only acts once the user has
     * actually clicked the link, so it never shows an error while waiting.
     */
    public function checkVerificationStatus(): void
    {
        $user = Auth::user();
        $user->refresh();

        if ($user->hasVerifiedEmail()) {
            session()->flash('success', __('Email verified successfully!'));
            $this->redirect(route('profile.edit'));
        }
    }

    public function confirmDeleteAccount(): void
    {
        $this->confirmingDeleteAccount = true;
    }

    public function cancelDeleteAccount(): void
    {
        $this->confirmingDeleteAccount = false;
        $this->deletePassword = '';
        $this->resetErrorBag('deletePassword');
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();

        if ($user->password) {
            $this->validate(['deletePassword' => 'required']);

            if (! Hash::check($this->deletePassword, $user->password)) {
                $this->addError('deletePassword', __('The password is incorrect.'));

                return;
            }
        }

        Auth::logout();
        $user->delete();

        session()->flash('success', __('Account deleted.'));
        $this->redirect(route('login'));
    }

    public function render(): View
    {
        $user = Auth::user();
        $remindersCount = $user->reminders()->count();
        $marksCount = $user->notificationEvents()->count();

        $remindersPhrase = trans_choice(':count reminder|:count reminders', $remindersCount, ['count' => $remindersCount]);
        $marksPhrase = trans_choice(':count mark|:count marks', $marksCount, ['count' => $marksCount]);

        return view('livewire.profile', [
            'user' => $user,
            'deleteAccountBody' => __(':reminders and :marks stop right away. Sign in within 30 days to restore them — after that they\'re deleted for good.', [
                'reminders' => $remindersPhrase,
                'marks' => $marksPhrase,
            ]),
        ]);
    }
}
