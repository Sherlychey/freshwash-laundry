<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::withCount('orders')->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.trim($request->query('q')).'%'))->orderBy('name')->paginate(12)->withQueryString();
        return view('admin.services.index', compact('services'));
    }
    public function create() { return view('admin.services.form', ['service' => new Service]); }
    public function store(Request $request)
    {
        Service::create($this->validated($request));
        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil ditambahkan.');
    }
    public function edit(Service $layanan) { return view('admin.services.form', ['service' => $layanan]); }
    public function update(Request $request, Service $layanan)
    {
        $layanan->update($this->validated($request));
        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil diperbarui. Tarif pada pesanan lama tetap memakai snapshot awal.');
    }
    public function destroy(Service $layanan)
    {
        if ($layanan->orders()->exists()) return back()->with('error', 'Layanan memiliki riwayat pesanan. Nonaktifkan layanan agar data lama tetap utuh.');
        $layanan->delete();
        return back()->with('success', 'Layanan berhasil dihapus.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['required','string','max:150'], 'description' => ['nullable','string','max:2000'], 'unit' => ['required','in:kg,item'], 'price' => ['required','numeric','decimal:0,2','min:0.01','max:9999999999'], 'duration_days' => ['required','integer','min:1','max:60'], 'is_active' => ['nullable','boolean']]) + ['is_active' => $request->boolean('is_active')];
    }
}
