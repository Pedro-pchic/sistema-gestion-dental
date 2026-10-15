<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DentistPublicProfileController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-dentists-public');

        return view('dentists-public.index', ['dentists' => Dentist::query()->with('user:id,name,is_active')->orderBy('id')->get()]);
    }

    public function update(Request $request, Dentist $dentist): RedirectResponse
    {
        Gate::authorize('manage-dentists-public');
        $data = $request->validate([
            'is_public' => ['required', 'boolean'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'public_bio' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        unset($data['photo']);
        $oldPhoto = $dentist->public_photo_path;

        if ($request->hasFile('photo')) {
            $data['public_photo_path'] = $request->file('photo')->store('dentists', 'public');
        }

        $dentist->update($data);

        if (isset($data['public_photo_path']) && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return back()->with('status', 'Perfil público actualizado.');
    }
}
