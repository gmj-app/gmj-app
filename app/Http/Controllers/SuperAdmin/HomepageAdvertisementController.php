<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomepageAdvertisementRequest;
use App\Models\HomepageAdvertisement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomepageAdvertisementController extends Controller
{
    public function index(): View
    {
        $advertisements = HomepageAdvertisement::orderBy('placement')->orderBy('created_at')->get();
        $conflicts = $advertisements->groupBy('placement')->filter(fn ($ads) => $ads->count() > 1)->keys();

        return view('super-admin.ads.index', compact('advertisements', 'conflicts'));
    }

    public function create(): View
    {
        return view('super-admin.ads.create');
    }

    public function store(HomepageAdvertisementRequest $request): RedirectResponse
    {
        $data = $this->data($request);
        $data['image_path'] = $this->storeImage($request);
        $data['created_by_user_id'] = $request->user()->id;
        $data['updated_by_user_id'] = $request->user()->id;
        HomepageAdvertisement::create($data);

        return redirect()->route('super-admin.ads.index')->with('success', 'Advertisement created.');
    }

    public function edit(HomepageAdvertisement $advertisement): View
    {
        return view('super-admin.ads.edit', compact('advertisement'));
    }

    public function update(HomepageAdvertisementRequest $request, HomepageAdvertisement $advertisement): RedirectResponse
    {
        $data = $this->data($request);
        $data['updated_by_user_id'] = $request->user()->id;
        $oldPath = null;
        if ($request->hasFile('image')) {
            $oldPath = $advertisement->image_path;
            $data['image_path'] = $this->storeImage($request);
        }
        $advertisement->update($data);
        if ($oldPath) {
            Storage::disk(config('filesystems.default'))->delete($oldPath);
        }

        return redirect()->route('super-admin.ads.index')->with('success', 'Advertisement updated.');
    }

    public function destroy(HomepageAdvertisement $advertisement): RedirectResponse
    {
        $advertisement->delete();

        return redirect()->route('super-admin.ads.index')->with('success', 'Advertisement deleted.');
    }

    public function toggle(HomepageAdvertisement $advertisement): RedirectResponse
    {
        $advertisement->update(['is_active' => ! $advertisement->is_active, 'updated_by_user_id' => auth()->id()]);

        return back()->with('success', $advertisement->is_active ? 'Advertisement enabled.' : 'Advertisement disabled.');
    }

    private function data(HomepageAdvertisementRequest $request): array
    {
        return $request->safe()->except('image');
    }

    private function storeImage(HomepageAdvertisementRequest $request): string
    {
        $file = $request->file('image');
        $path = 'advertisements/homepage/'.Str::uuid().'.'.$file->extension();
        Storage::disk(config('filesystems.default'))->putFileAs('advertisements/homepage', $file, basename($path));

        return $path;
    }
}
