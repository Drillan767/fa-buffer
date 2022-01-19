<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendLessonChanged;
use App\Http\Requests\{SubscriptionValidationRequest, UserUpdateRequest};
use Illuminate\Http\Request;
use App\Models\{Lesson, Service, Subscription, User};
use App\Services\SubscriptionHandler;
use Illuminate\Http\RedirectResponse;
use Inertia\{Inertia, Response};

class UserController extends Controller
{
    public function __construct(protected SubscriptionHandler $subscriptionHandler)
    {}

    public function index(): Response
    {
        $roles = config('roles');
        $lessons = Lesson::all('id', 'title');
        $users = User::with('subscription.lesson')->get();

        return Inertia::render('Admin/Users/List', compact('users', 'lessons', 'roles'));
    }

    public function create()
    {

    }

    public function store()
    {

    }

    public function show(User $utilisateur)
    {
        $utilisateur->load('currentYearData.file', 'lesson', 'subscription', 'suggestions', 'firstContactData');
        $lessons = Lesson::all('id', 'title')->mapWithKeys(fn ($l) => [$l->id => $l->title]);

        return Inertia::render('Admin/Users/Show', [
            'currentUser' => $utilisateur,
            'services' => Service::all(['id', 'title']),
            'lessons' => $lessons,
        ]);
    }

    public function changeLesson(Request $request)
    {
        $validated = $request->validate([
            'user' => ['required', 'exists:users,id'],
            'lid' => ['required', 'exists:lessons,id'],
        ]);

        $user = User::with('subscription', 'lesson')->find($validated['user']);
        if ($validated['lid'] !== $user->lesson_id) {
            $user->lesson_id = $validated['lid'];
            $user->save();
            $user->subscription->update(['lesson_id' => $validated['lid']]);

            SendLessonChanged::dispatchAfterResponse($user, $validated['lid']);
            return redirect()->back()->with('success', 'Le cours a bien été changé.');
            // return redirect()->back()
        }
    }

    public function edit(User $utilisateur)
    {
        return Inertia::render('Admin/Users/Edit',
            [
                'subscriber' => $utilisateur->load('subscription.lesson', 'yearDatas'),
            ]
        );
    }

    public function update(UserUpdateRequest $request, User $utilisateur)
    {
        $fields = [
            'firstname',
            'lastname',
            'email',
            'gender',
            'birthday',
            'other_data',
            'address1',
            'address2',
            'zipcode',
            'city',
            'phone',
            'pro',
        ];

        foreach ($fields as $field) {
            $utilisateur->$field = $request->get($field);
        }

        $utilisateur->save();

        $utilisateur->suggestions()->sync($request->get('suggestions'));

        return redirect()->route('utilisateurs.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $utilisateur)
    {
        $utilisateur->delete();
    }

    public function updateLesson(Request $request)
    {

    }

    public function archiveUser(User $user)
    {
        $user->roles()->detach();
        $user->assignRole('archived');
    }

    public function preSubscribed(): Response
    {
        $users = User::role('subscriber')
            ->has('subscription')
            ->with('subscription', function($query) {
                $query->whereIn('status', [
                    Subscription::PENDING,
                    Subscription::NEEDS_INFOS,
                    Subscription::AWAITING_PAYMENT
                ]);
            })
            ->where('lesson_id', null)
            ->get();

        return Inertia::render('Admin/Users/Presubscribed', compact('users'));
    }

    public function subscribing(User $user): Response
    {
        return Inertia::render('Admin/Users/Subscribing',
            [
                'subscriber' => $user->load('subscription.lesson', 'currentYearData.file')
            ]
        );
    }

    public function subscribe(SubscriptionValidationRequest $request): RedirectResponse
    {
        list($route, $type, $message) = $this->subscriptionHandler->validate($request);
        return redirect()->route($route)->with($type, $message);
    }

    public function redirectHome(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        if ($request->ajax()) {
            return Inertia::location(route('landing'));
        }

        return redirect()->route('landing');
    }
}
