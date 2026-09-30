<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Accounts overview. Only admins reach this route, and no personal data
     * is exposed beyond what an administrator needs to act on.
     */
    public function index(Request $request): View
    {
        $users = User::query()
            ->when(filled($request->input('q')), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->withCount(['items', 'claims'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }
}
