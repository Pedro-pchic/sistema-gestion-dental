<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Dentist;
use App\Models\PublicSiteSetting;
use App\Models\Role;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'settings' => PublicSiteSetting::query()->find(1) ?? new PublicSiteSetting,
            'treatments' => Treatment::query()->where('is_active', true)->where('is_public', true)->orderBy('name')->get(),
            'dentists' => Dentist::query()->with('user:id,name')->where('is_active', true)->where('is_public', true)
                ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
                ->whereHas('user.role', fn (Builder $query): Builder => $query->where('name', Role::DENTIST))
                ->orderBy('id')->get(),
            'announcements' => Announcement::query()->visible()->orderBy('sort_order')->latest('published_on')->get(),
        ]);
    }
}
