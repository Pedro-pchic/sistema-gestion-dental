<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-announcements');

        return view('announcements.index', [
            'announcements' => Announcement::query()->orderBy('sort_order')->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-announcements');

        return view('announcements.form', ['announcement' => new Announcement]);
    }

    public function edit(Announcement $announcement): View
    {
        Gate::authorize('manage-announcements');

        return view('announcements.form', compact('announcement'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-announcements');
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        Announcement::query()->create($data);

        return redirect()->route('anuncios.index')->with('status', 'Anuncio guardado.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('manage-announcements');
        $data = $this->validated($request);
        $oldImage = $announcement->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($data);

        if (isset($data['image_path']) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('anuncios.index')->with('status', 'Anuncio actualizado.');
    }

    public function toggle(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('manage-announcements');

        if (! $announcement->is_published && ! $announcement->published_on) {
            return back()->withErrors(['published_on' => 'Define una fecha de publicación.']);
        }

        $announcement->update(['is_published' => ! $announcement->is_published]);

        return back()->with('status', 'Estado del anuncio actualizado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'published_on' => ['nullable', 'date_format:Y-m-d'],
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:published_on'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_published' => ['required', 'boolean'],
        ]);
        unset($data['image']);

        if ($data['is_published'] && empty($data['published_on'])) {
            throw ValidationException::withMessages(['published_on' => 'Indica la fecha de publicación.']);
        }

        return $data;
    }
}
