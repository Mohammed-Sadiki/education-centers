<?php
namespace App\Http\Controllers;
use App\Models\Announcement;
use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
class AnnouncementController extends Controller {
    public function index(Request $request): View {
        Gate::authorize('viewAny', Announcement::class);
        $user = $request->user();
        $query = Announcement::query()->with('author');
        if ($user->isCenterAdmin()) $query->where(fn($q) => $q->where('center_id',$user->center_id)->orWhereNull('center_id'));
        if ($status = $request->input('status')) $query->where('status',$status);
        $announcements = $query->latest()->paginate(15)->withQueryString();
        $centers = $user->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        return view('announcements.index', compact('announcements','centers'));
    }
    public function create(): View {
        Gate::authorize('create', Announcement::class);
        $centers = auth()->user()->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        return view('announcements.create', compact('centers'));
    }
    public function store(Request $request): RedirectResponse {
        Gate::authorize('create', Announcement::class);
        $user = $request->user();
        $data = $request->validate([
            'title'     => ['required','string','max:255'],
            'content'   => ['required','string'],
            'is_public' => ['boolean'],
            'status'    => ['required','in:draft,published,archived'],
            'center_id' => ['nullable','integer','exists:centers,id'],
        ]);
        if ($user->isCenterAdmin()) $data['center_id'] = $user->center_id;
        $data['author_id'] = $user->id;
        if ($data['status'] === 'published' && empty($data['published_at'])) $data['published_at'] = now();
        $a = Announcement::create($data);
        return redirect()->route('announcements.show', $a)->with('success', "Annonce \"{$a->title}\" créée.");
    }
    public function show(Announcement $announcement): View {
        Gate::authorize('view', $announcement);
        return view('announcements.show', compact('announcement'));
    }
    public function edit(Announcement $announcement): View {
        Gate::authorize('update', $announcement);
        $centers = auth()->user()->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        return view('announcements.edit', compact('announcement','centers'));
    }
    public function update(Request $request, Announcement $announcement): RedirectResponse {
        Gate::authorize('update', $announcement);
        $data = $request->validate([
            'title'     => ['required','string','max:255'],
            'content'   => ['required','string'],
            'is_public' => ['boolean'],
            'status'    => ['required','in:draft,published,archived'],
        ]);
        if ($data['status'] === 'published' && !$announcement->published_at) $data['published_at'] = now();
        $announcement->update($data);
        return redirect()->route('announcements.show', $announcement)->with('success', 'Annonce mise à jour.');
    }
    public function destroy(Announcement $announcement): RedirectResponse {
        Gate::authorize('delete', $announcement);
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Annonce supprimée.');
    }
}