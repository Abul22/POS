<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\PayUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize(new User());

        $users = User::sortable()->get();

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize(new User());

        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateUserRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        $this->authorize(new User());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'balance' => $request->balance,
            'password' => bcrypt($request->password)
        ]);

        return redirect()->route('user.show', $user)->with('success', 'User created');
    }

    /**
     * Display the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize($user);

        $sales = $user->sales;
        $sales->load('product');

        return view('users.show', compact('user', 'sales'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $this->authorize($user);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest|Request $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize($user);

        if ($request->has('password'))
            $user->password = bcrypt($request->password);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->balance = $request->balance;

        $user->save();

        return redirect()->route('user.show', $user)->with('success', 'User updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize($user);

        $user->delete();

        return redirect()->route('user.index')->with('success', 'User deleted');
    }

    /**
     * Pay an amount
     *
     * @param PayUserRequest $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function pay(PayUserRequest $request, User $user)
    {
        $user->balance += $request->amount;

        $user->save();

        return redirect()->route('user.show', $user)->with('success', 'Amount Paid');
    }
}
