<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['current_password', 'password', 'password_confirmation', 'avatar']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $file = $request->file('avatar');
            $webpContents = (string) (new ImageManager(new ImagickDriver))
                ->decodeBinary(file_get_contents($file->getRealPath()))
                ->encode(new WebpEncoder(quality: 80));

            $path = 'avatars/'.Str::uuid().'.webp';
            Storage::disk('public')->put($path, $webpContents);
            $data['avatar'] = $path;
        }

        $user->fill($data);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->validated('password'));
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated successfully.');
    }
}
