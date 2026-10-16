@extends('layouts.admin')

@section('title', 'Detail '.$order->order_code)
@section('page', 'Detail Pesanan')

@section('content')
<div class="detail-back">
    <a href="{{ route('admin.orders.index') }}">← Kembali ke semua pesanan</a>
    <span class="status-badge status-{{ $order->order_status }}">{{ \App\Models\Order::labelForStatus($order->order_status) }}</span>
</div>

<div class="page-heading detail-heading">
    <div><span class="eyebrow">DETAIL PESANAN</span><h1>{{ $order->order_code }}</h1><p>Dibuat {{ $order->created_at->format('d M Y, H:i') }} · Sumber {{ $order->source === 'online' ? 'online' : 'admin' }}</p></div>
</div>

@if(session('success'))
    <div class="success-alert" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="order-detail-grid">
    <div class="detail-column">
        <section class="panel detail-panel">
            <div class="panel-heading"><div><h2>Informasi pelanggan</h2><p>Data untuk kebutuhan operasional internal.</p></div></div>
            <dl class="detail-list">
                <div><dt>Nama</dt><dd>{{ $order->customer->name }}</dd></div>
                <div><dt>Nomor HP</dt><dd><a href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a></dd></div>
                <div><dt>Email</dt><dd>{{ $order->customer->email ?: '—' }}</dd></div>
                <div><dt>Alamat pelanggan</dt><dd>{{ $order->customer->address ?: '—' }}</dd></div>
                <div><dt>Metode penyerahan</dt><dd>{{ $order->delivery_method === 'pickup_delivery' ? 'Penjemputan diminta' : 'Datang langsung' }}</dd></div>
                @if($order->delivery_address)
                    <div><dt>Alamat penjemputan</dt><dd>{{ $order->delivery_address }}</dd></div>
                @endif
                @if($order->pickup_schedule)
                    <div><dt>Jadwal penjemputan</dt><dd>{{ $order->pickup_schedule->format('d M Y, H:i') }}</dd></div>
                @endif
            </dl>
        </section>

        <section class="panel detail-panel">
            <div class="panel-heading"><div><h2>Rincian layanan</h2><p>Snapshot tarif saat pesanan dibuat.</p></div></div>
            <dl class="detail-list">
                <div><dt>Layanan</dt><dd>{{ $order->service->name }}</dd></div>
                <div><dt>Kuantitas</dt><dd>@if($order->service->unit === 'kg'){{ $order->weight_kg ? $order->weight_kg.' kg' : 'Menunggu penimbangan' }}@else{{ $order->quantity_items.' item' }}@endif</dd></div>
                <div><dt>Harga per {{ $order->service->unit === 'kg' ? 'kg' : 'item' }}</dt><dd>Rp{{ number_format((float) $order->unit_price, 0, ',', '.') }}</dd></div>
                <div><dt>Estimasi selesai</dt><dd>{{ optional($order->estimated_completion_at)->format('d M Y') ?? '—' }}</dd></div>
                <div><dt>Catatan pelanggan</dt><dd>{{ $order->notes ?: 'Tidak ada catatan' }}</dd></div>
                <div class="detail-total">
                    <dt>{{ $order->amount_finalized_at ? 'Total tagihan' : 'Estimasi tagihan' }}</dt>
                    <dd>
                        @if($order->amount_finalized_at)
                            Rp{{ number_format((float) $order->total_amount, 0, ',', '.') }}
                        @elseif($order->service->unit === 'kg' && $order->weight_kg === null)
                            Menunggu penimbangan
                        @else
                            Rp{{ number_format((float) $order->total_amount, 0, ',', '.') }} · menunggu konfirmasi
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="detail-column">
        <section class="panel detail-panel action-panel">
            <div class="panel-heading"><div><h2>Tindakan pesanan</h2><p>Status diperbarui melalui alur yang diizinkan.</p></div></div>
            @if($order->order_status === 'pending_confirmation')
                <form method="POST" action="{{ route('admin.orders.accept', $order) }}">
                    @csrf
                    <button class="button button-primary action-full" type="submit">Terima pesanan <span>→</span></button>
                </form>
                <form method="POST" action="{{ route('admin.orders.reject', $order) }}" class="reject-form" data-confirm-reject>
                    @csrf
                    <label for="reason">Alasan penolakan <b>*</b></label>
                    <textarea id="reason" name="reason" required minlength="5" maxlength="1000" rows="3" placeholder="Jelaskan alasan penolakan"></textarea>
                    <button class="button button-danger action-full" type="submit">Tolak pesanan</button>
                </form>
            @elseif($order->order_status === 'awaiting_weighing' && $order->amount_finalized_at === null && $order->service->unit === 'kg')
                <form method="POST" action="{{ route('admin.orders.weigh', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label for="actual-weight">Berat aktual <b>*</b></label>
                    <div class="input-suffix"><input id="actual-weight" name="weight_kg" type="number" min="0.1" max="500" step="0.01" value="{{ old('weight_kg') }}" required placeholder="Contoh: 2.50"><span>kg</span></div>
                    <small class="field-hint">Tagihan final dihitung dari snapshot harga {{ number_format((float) $order->unit_price, 0, ',', '.') }}/kg.</small>
                    <button class="button button-primary action-full" type="submit">Simpan berat & mulai cuci <span>→</span></button>
                </form>
            @elseif($order->order_status === 'awaiting_weighing' && $order->amount_finalized_at === null && $order->service->unit === 'item')
                <form method="POST" action="{{ route('admin.orders.confirm-quantity', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label for="actual-quantity">Jumlah aktual <b>*</b></label>
                    <div class="input-suffix"><input id="actual-quantity" name="quantity_items" type="number" min="1" max="1000" step="1" value="{{ old('quantity_items', $order->quantity_items) }}" required><span>item</span></div>
                    <small class="field-hint">Tagihan final dihitung dari snapshot harga {{ number_format((float) $order->unit_price, 0, ',', '.') }}/item.</small>
                    <button class="button button-primary action-full" type="submit">Konfirmasi jumlah & mulai cuci <span>→</span></button>
                </form>
            @elseif(count($transitions))
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label for="next-status">Langkah berikutnya</label>
                    <select id="next-status" name="status" required>
                        @foreach($transitions as $transition)
                            <option value="{{ $transition }}">{{ \App\Models\Order::labelForStatus($transition) }}</option>
                        @endforeach
                    </select>
                    <label for="status-note">Catatan <small>opsional</small></label>
                    <textarea id="status-note" name="note" maxlength="1000" rows="3" placeholder="Catatan perubahan"></textarea>
                    <button class="button button-primary action-full" type="submit">Perbarui status <span>→</span></button>
                </form>
            @else
                <p class="terminal-note">Pesanan ini sudah mencapai status akhir.</p>
            @endif
        </section>

        <section class="panel detail-panel">
            <div class="panel-heading"><div><h2>Riwayat status</h2><p>Perubahan status yang tercatat.</p></div></div>
            <ol class="history-list">
                @forelse($order->statusHistories->sortByDesc('created_at') as $history)
                    <li>
                        <span class="history-dot"></span>
                        <div>
                            <strong>{{ \App\Models\Order::labelForStatus($history->to_status) }}</strong>
                            <small>{{ $history->created_at->format('d M Y, H:i') }}
                                @if($history->user) · {{ $history->user->name }} @else · Sistem @endif
                            </small>
                            @if($history->note)<p>{{ $history->note }}</p>@endif
                        </div>
                    </li>
                @empty
                    <li>Belum ada riwayat.</li>
                @endforelse
            </ol>
        </section>
    </div>
</div>
<section class="panel detail-panel payment-panel"><div class="panel-heading"><div><h2>Pembayaran</h2><p>Catat pembayaran sebagian atau lunas dan verifikasi penerimaan.</p></div><a class="table-action" href="{{ route('admin.orders.receipt',$order) }}">Cetak nota</a></div>
@php($verifiedTotal = (float) $order->payments->where('status','verified')->sum('amount')) @php($pendingTotal = (float) $order->payments->where('status','pending')->sum('amount'))
<div class="payment-summary"><span>Tagihan <strong>Rp{{ number_format((float)$order->total_amount,0,',','.') }}</strong></span><span>Diterima <strong>Rp{{ number_format($verifiedTotal,0,',','.') }}</strong></span><span>Menunggu <strong>Rp{{ number_format($pendingTotal,0,',','.') }}</strong></span><span>Sisa <strong>Rp{{ number_format(max(0,(float)$order->total_amount-$verifiedTotal),0,',','.') }}</strong></span></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>WAKTU</th><th>METODE</th><th>JUMLAH</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>@forelse($order->payments as $payment)<tr><td>{{ $payment->paid_at?->format('d/m/Y H:i')??$payment->created_at->format('d/m/Y H:i') }}</td><td>{{ ['cash'=>'Tunai','bank_transfer'=>'Transfer bank','qris'=>'QRIS'][$payment->method]??$payment->method }}</td><td>Rp{{ number_format((float)$payment->amount,0,',','.') }}</td><td>{{ $payment->status==='verified'?'Terverifikasi':($payment->status==='pending'?'Menunggu':'Ditolak') }}</td><td>@if($payment->status==='pending')<form method="POST" action="{{ route('admin.payments.verify',$payment) }}">@csrf @method('PATCH')<button class="table-action">Verifikasi</button></form>@else—@endif</td></tr>@empty<tr><td colspan="5">Belum ada pembayaran.</td></tr>@endforelse</tbody></table></div>
@if($order->amount_finalized_at && $verifiedTotal+$pendingTotal < (float)$order->total_amount)<form class="payment-entry" method="POST" action="{{ route('admin.payments.store',$order) }}">@csrf<div class="form-grid"><div class="field"><label>Jumlah pembayaran (Rp)</label><input type="number" name="amount" min="0.01" max="{{ max(0,(float)$order->total_amount-$verifiedTotal-$pendingTotal) }}" step="0.01" required></div><div class="field"><label>Metode</label><select name="method"><option value="cash">Tunai</option><option value="bank_transfer">Transfer bank</option><option value="qris">QRIS</option></select></div><div class="field"><label>Referensi transaksi</label><input name="reference" maxlength="255"></div><div class="field"><label>Catatan</label><input name="notes" maxlength="1000"></div></div><button class="button button-primary">Catat pembayaran</button></form>@elseif(!$order->amount_finalized_at)<p class="terminal-note">Pembayaran tersedia setelah tagihan final dihitung.</p>@endif
</section>
@endsection
