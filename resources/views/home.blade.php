@extends('layouts.public')

@section('title', 'FreshWash — Pakaian bersih, waktu lebih berarti')

@section('content')
<header class="site-header">
    <a class="brand" href="{{ route('home') }}" aria-label="FreshWash beranda"><span class="brand-mark">F</span><span>fresh<span class="brand-light">wash</span><small>LAUNDRY CARE</small></span></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
    <nav class="main-nav" id="main-nav" aria-label="Navigasi utama">
        <a href="#home">Beranda</a><a href="#services">Layanan</a><a href="#how">Cara kerja</a><a href="{{ route('orders.track') }}">Lacak pesanan</a>
        <a class="nav-login" href="{{ route('orders.create') }}">Pesan laundry</a><a class="nav-admin-link" href="{{ route('login') }}">Masuk admin</a>
    </nav>
</header>
<main>
    <section class="hero" id="home">
        <div class="hero-copy"><span class="eyebrow"><span class="eyebrow-dot"></span> FRESHWASH LAUNDRY CARE</span>
            <h1>Pakaian bersih,<br><span>waktu lebih berarti.</span></h1>
            <p>Perawatan laundry yang teliti, praktis, dan tepat waktu. Serahkan cucian Anda, kami urus sisanya.</p>
            <div class="hero-actions"><a class="button button-primary" href="{{ route('orders.create') }}">Pesan sekarang <span aria-hidden="true">→</span></a><a class="text-link" href="{{ route('orders.track') }}">Lacak pesanan</a></div>
            <div class="hero-proof"><div class="proof-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></div><span><strong>Dirawat dengan teliti</strong><small>Setiap helai mendapat perhatian</small></span><span class="proof-divider"></span><span class="proof-stars" aria-label="Layanan terpercaya">&#9733;&#9733;&#9733;&#9733;&#9733;<small>Dipercaya pelanggan lokal</small></span></div>
        </div>
        <div class="hero-art" aria-label="Ilustrasi pakaian bersih FreshWash" role="img"><div class="art-orbit"></div><div class="art-spark spark-one" aria-hidden="true">+</div><div class="art-spark spark-two" aria-hidden="true">✦</div><div class="laundry-stack"><div class="towel towel-back"></div><div class="towel towel-mid"></div><div class="towel towel-front"><span class="towel-fold"></span></div><div class="shirt-shape"><span></span></div></div><div class="floating-card card-pickup"><span class="mini-icon" aria-hidden="true">✓</span><span><strong>Siap diambil</strong><small>Lebih cepat, lebih praktis</small></span></div><div class="floating-card card-care"><span class="care-icon" aria-hidden="true">✦</span><span><strong>Perawatan lembut</strong><small>Untuk pakaian kesayangan</small></span></div><div class="art-caption">FRESH FEELING, EVERY DAY</div></div>
    </section>
    <section class="trust-strip"><span>PERAWATAN PILIHAN UNTUK SETIAP HARI</span><div><b>CUCI &amp; LIPAT</b><i></i><b>CUCI SETRIKA</b><i></i><b>PAKAIAN SPESIAL</b><i></i><b>ANTAR JEMPUT</b></div></section>
    <section class="section services-section" id="services"><div class="section-heading"><div><span class="eyebrow">LAYANAN KAMI</span><h2>Perawatan yang pas<br>untuk <em>kebutuhan Anda.</em></h2></div><p>Pilih perawatan yang sesuai. Harga transparan, hasil selalu kami jaga.</p></div>
        <div class="service-grid">
            @forelse($services as $service)
                <article class="service-card"><span class="service-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM7 4h10M8 10h8M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01M16 17h.01"/></svg></span><span class="service-duration">{{ $service->duration_days }} hari</span><h3>{{ $service->name }}</h3><p>{{ $service->description ?: 'Perawatan bersih dan rapi untuk pakaian sehari-hari Anda.' }}</p><div class="service-price">Rp{{ number_format((float) $service->price, 0, ',', '.') }}<small> / {{ $service->unit === 'kg' ? 'kg' : 'item' }}</small></div><a href="{{ route('orders.create', ['service_id' => $service->id]) }}" class="service-link">Pesan layanan <span aria-hidden="true">→</span></a></article>
            @empty
                <div class="empty-services">Layanan akan segera tersedia. Silakan hubungi kami untuk informasi lebih lanjut.</div>
            @endforelse
        </div>
    </section>
    <section class="why-section"><div class="why-art"><div class="wash-circle"><span>FW</span><i class="bubble bubble-a"></i><i class="bubble bubble-b"></i><i class="bubble bubble-c"></i></div><div class="why-note"><strong>Rapi. Bersih. Siap.</strong><small>Setiap kali.</small></div></div><div class="why-copy"><span class="eyebrow">KENAPA FRESHWASH</span><h2>Perhatian kecil,<br><em>perbedaan besar.</em></h2><p>Kami percaya pakaian bersih bisa membuat hari terasa lebih baik. Karena itu setiap proses kami jalankan dengan hati-hati.</p><div class="benefit-list"><div><span>01</span><p><strong>Perawatan penuh perhatian</strong><small>Pakaian dipilah dan dirawat sesuai jenis kain.</small></p></div><div><span>02</span><p><strong>Waktu yang bisa diandalkan</strong><small>Estimasi jelas agar Anda mudah merencanakan hari.</small></p></div><div><span>03</span><p><strong>Harga transparan</strong><small>Biaya dihitung jelas berdasarkan layanan yang dipilih.</small></p></div></div></div></section>
    <section class="how-section" id="how"><span class="eyebrow">MUDAH DARI AWAL</span><h2>Empat langkah, selesai.</h2><div class="steps-grid"><article><span>01</span><h3>Pilih layanan</h3><p>Temukan perawatan yang paling sesuai dengan kebutuhan Anda.</p></article><article><span>02</span><h3>Isi pemesanan</h3><p>Masukkan data dan pilih cara penyerahan cucian.</p></article><article><span>03</span><h3>Tunggu konfirmasi</h3><p>Tim kami akan mengonfirmasi detail dan jadwal pesanan.</p></article><article><span>04</span><h3>Pantau status</h3><p>Gunakan kode dan token untuk melihat perkembangan laundry.</p></article></div></section>
    <section class="contact-cta" id="contact"><div><span class="eyebrow">KAMI SIAP MEMBANTU</span><h2>Mulai hari dengan<br>pakaian yang segar.</h2><p>Atur pesanan Anda melalui formulir online kami.</p></div><a class="button button-light" href="{{ route('orders.create') }}">Pesan laundry <span aria-hidden="true">→</span></a></section>
</main>
<footer class="site-footer"><a class="brand brand-footer" href="#home"><span class="brand-mark">F</span><span>fresh<span class="brand-light">wash</span><small>LAUNDRY CARE</small></span></a><span>Perawatan pakaian dengan perhatian.</span><a class="nav-admin-link" href="{{ route('login') }}">Portal admin</a><small>© {{ now()->year }} FreshWash Laundry.</small></footer>
@endsection
