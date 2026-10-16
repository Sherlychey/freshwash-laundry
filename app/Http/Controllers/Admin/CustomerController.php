<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::withCount('orders')->when($request->filled('q'), function ($query) use ($request) {
            $term = trim($request->query('q'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        })->latest()->paginate(12)->withQueryString();
        return view('admin.customers.index', compact('customers'));
    }

    public function create() { return view('admin.customers.form', ['customer' => new Customer]); }
    public function store(Request $request)
    {
        Customer::create($this->validated($request));
        return redirect()->route('admin.customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }
    public function edit(Customer $pelanggan) { return view('admin.customers.form', ['customer' => $pelanggan]); }
    public function update(Request $request, Customer $pelanggan)
    {
        $pelanggan->update($this->validated($request));
        return redirect()->route('admin.customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }
    public function destroy(Customer $pelanggan)
    {
        if ($pelanggan->orders()->exists()) return back()->with('error', 'Pelanggan memiliki riwayat pesanan dan tidak dapat dihapus.');
        $pelanggan->delete();
        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['required','string','max:150'], 'gender' => ['nullable','in:male,female'], 'phone' => ['required','string','max:30'], 'email' => ['nullable','email','max:150'], 'address' => ['nullable','string','max:2000']]);
    }
}
