@extends('layouts.public')

@section('title', 'Lacak Pesanan — FreshWash')

@section('content')
<header class="site-header">
    <a class="brand" href="{{ route('home') }}"><span class="brand-mark">F</span><span>fresh<span class="brand-light">wash</span><small>LAUNDRY CARE</small></span></a>
    <a class="nav-login" href="{{ route('home') }}">Beranda</a>
</header>

<main class="track-page">
    <div class="track-intro">
        <span class="eyebrow">STATUS PESANAN</span>
        <h1>Setiap tahap,<br><em>tetap terpantau.</em></h1>
        <p>Masukkan kode pesanan dan token pelacakan dari halaman konfirmasi.</p>
    </div>

    <div class="track-card">
        <form method="GET" action="{{ route('orders.track') }}" class="track-form">
            <div class="field"><label for="code">Kode pesanan</label><input id="code" name="code" value="{{ $code ?? '' }}" required maxlength="30" placeholder="FW-YYMMDD-XXXXXXXX"></div>
            <div class="field"><label for="token">Token pelacakan</label><input id="token" name="token" value="{{ $token ?? '' }}" required minlength="48" maxlength="48" autocomplete="off" placeholder="48 karakter dari tautan konfirmasi"></div>
            <button type="submit" class="button button-primary">Lacak pesanan <span>→</span></button>
        </form>

        @if($error)
            <div class="track-error" role="alert">{{ $error }}</div>
        @endif

        @if($order)
            <section class="track-result">
                <div class="track-result-heading">
                    <span class="eyebrow">{{ $order->order_code }}</span>
                    <span class="status-badge status-{{ $order->order_status }}">{{ \App\Models\Order::labelForStatus($order->order_status) }}</span>
                </div>
                <dl>
                    <div><dt>Layanan</dt><dd>{{ $order->service->name }}</dd></div>
                    <div><dt>Tanggal pesanan</dt><dd>{{ $order->created_at->format('d M Y') }}</dd></div>
                    <div><dt>Estimasi selesai</dt><dd>{{ optional($order->estimated_completion_at)->format('d M Y') ?? 'Belum ditentukan' }}</dd></div>
                    <div>
                        <dt>Tagihan</dt>
                        <dd>
                            @if($order->amount_finalized_at === null && $order->service->unit === 'kg' && $order->weight_kg === null)
                                Menunggu penimbangan
                            @elseif($order->amount_finalized_at === null)
                                Menunggu konfirmasi tagihan
                            @else
                                Rp{{ number_format((float) $order->total_amount, 0, ',', '.') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Pembayaran</dt>
                        <dd>
                            @php($paid = (float) $order->payments->where('status', 'verified')->sum('amount'))
                            @if($order->amount_finalized_at === null)
                                Menunggu perhitungan final
                            @elseif($paid <= 0)
                                Belum bayar
                            @elseif($paid >= (float) $order->total_amount)
                                Lunas
                            @else
                                Sebagian
                            @endif
                        </dd>
                    </div>
                    <div><dt>Penyerahan</dt><dd>{{ $order->delivery_method === 'pickup_delivery' ? 'Penjemputan diminta' : 'Datang langsung' }}</dd></div>
                </dl>
                <div class="public-timeline"><h2>Perjalanan pesanan</h2><ol>@foreach($order->statusHistories as $history)<li class="{{ $loop->last ? 'current' : 'complete' }}"><span class="timeline-point" aria-hidden="true"></span><div><strong>{{ \App\Models\Order::labelForStatus($history->to_status) }}</strong><time datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->format('d M Y, H:i') }}</time></div></li>@endforeach</ol></div>
                <p class="track-privacy">Informasi kontak dan alamat pelanggan tidak ditampilkan di halaman publik.</p>
            </section>
        @endif
    </div>
    <a class="result-home" href="{{ route('home') }}">← Kembali ke beranda</a>
</main>
@endsection
