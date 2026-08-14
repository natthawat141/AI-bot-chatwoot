<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessProfileRequest;
use App\Http\Resources\BusinessProfileResource;
use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Singleton resource: no index/create/destroy, just edit the one row.
 */
class BusinessProfileController extends Controller
{
    public function edit(): Response
    {
        $profile = BusinessProfile::current();
        Gate::authorize('view', $profile);

        return Inertia::render('BusinessProfile/Form', ['profile' => new BusinessProfileResource($profile)]);
    }

    public function update(BusinessProfileRequest $request): RedirectResponse
    {
        $profile = BusinessProfile::current();
        Gate::authorize('update', $profile);

        $profile->update($request->validated());

        return redirect()->route('admin.business-profile.edit')->with('success', 'บันทึกโปรไฟล์ธุรกิจเรียบร้อยแล้ว');
    }
}
