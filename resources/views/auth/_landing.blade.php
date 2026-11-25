{{--
    Landing page tamu CargoTrace (dipakai login & register). Login/daftar tetap di popup (partials/auth-pill).
    Urutan: Hero -> Mengapa Memilih CargoTrace? (3x2) -> Tentang Kami -> Fitur Unggulan -> Cara Kerja -> CTA -> Footer.
    Teks sumber berbahasa Inggris; terjemahan ada di public/js/ct-dict.js (pola yang sama dengan halaman lain).
    Gaya: public/css/landing.css, perilaku: public/js/landing.js.
--}}
@php
    $ic = [
        'pin'    => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle class="acc" cx="12" cy="10" r="2.5"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path class="acc" d="M12 7v5l3.5 2"/>',
        'card'   => '<rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10.5h18"/><path class="acc" d="M7 15h4"/>',
        'plane'  => '<path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
        'ship'   => '<path d="M3 15l2 5h14l2-5-9-3z"/><path class="acc" d="M12 12V4l5 4"/>',
        'truck'  => '<path d="M1 6h13v10H1zM14 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="2"/><circle class="acc" cx="17" cy="18" r="2"/>',
        'doc'    => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path class="acc" d="M10 12h5M10 16h5"/>',
        'shield' => '<path d="M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6z"/><path class="acc" d="M9 12l2.2 2.2L15.5 10"/>',
        'map'    => '<path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path class="acc" d="M9 4v14M15 6v14"/>',
        'alert'  => '<path d="M12 3l10 18H2z"/><path class="acc" d="M12 10v5M12 18v.5"/>',
        'user'   => '<circle cx="10" cy="8" r="4"/><path d="M2 21a8 8 0 0 1 16 0"/><path class="acc" d="M19 8v6M16 11h6"/>',
        'box'    => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path class="acc" d="M3 7l9 4 9-4M12 11v10"/>',
        'done'   => '<circle cx="12" cy="12" r="9"/><path class="acc" d="M8 12.5l2.7 2.7L16 9.5"/>',
        'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'up'     => '<path d="M6 15l6-6 6 6"/>',
        'headset' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h2.5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4Zm16 0h-2.5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1H19a1 1 0 0 0 1-1v-4Z"/><path d="M18 19c0 1.5-1.5 2-4 2"/>',
    ];
    $svg = fn (string $n, string $cls = '') => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$ic[$n].'</svg>';
    $heroUrl = asset('images/login-hero.jpg').'?v='.filemtime(public_path('images/login-hero.jpg'));
    $img = fn (string $f) => asset('images/'.$f).'?v='.filemtime(public_path('images/'.$f));
    $mark = '<svg width="34" height="34" viewBox="0 0 34 34" aria-hidden="true"><defs><linearGradient id="lpg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e11d74"/><stop offset="1" stop-color="#f06aa5"/></linearGradient></defs><circle cx="17" cy="17" r="17" fill="url(#lpg)"/><path d="M9 20.5l16-6.5-4.5 11-3-4-4 1.5v-3.5z" fill="#fff"/><circle cx="25" cy="9" r="2.4" fill="#bfe3a4"/></svg>';
    $why = [
        ['pin',    'Real-Time Tracking',            'See every shipment on a live world map with city and country names, from origin to destination.'],
        ['clock',  'Automatic ETA & Delay Detection', 'Estimated arrival times are calculated automatically and delays are flagged the moment they happen.'],
        ['truck',  'Automatic Delivery',            'Shipments move on their own from booking to delivery, and every step is saved in the tracking history.'],
        ['plane',  'Air, Sea & Road Coverage',      'Plane, ship and truck routes in one place, each with its own icon on the map.'],
        ['doc',    'Complete Reports',              'Download PDF reports and accounting exports whenever you need them.'],
        ['shield', 'Audit Trail & Role Access',     'Every action is logged, and admins, logistics staff and customers each see only what they need.'],
    ];
    $feat = [
        ['map',   'Interactive World Map',    'Zoom across continents and follow each shipment’s route, with a plane icon for air freight and a truck icon for road freight.'],
        ['alert', 'Smart Delay Management',   'Report and resolve delays with clear reasons, and notify the right people automatically.'],
        ['doc',   'Complete Tracking History', 'Every position and status change is stored, so you can review the whole journey of each shipment.'],
    ];
    $chips = ['7 languages', 'PDF reports', 'Audit logs', 'Weather info', 'Notifications', 'Accounting'];
    $steps = [
        ['user', 'Create an account', 'Register as a customer in seconds and verify your email.'],
        ['box',  'Book a shipment',   'Enter the origin, destination, carrier and cargo details.'],
        ['truck', 'Ship automatically', 'The system moves your cargo along its route by itself.'],
        ['done', 'Track & receive',   'Follow the route live until your cargo is delivered.'],
    ];
@endphp

{{-- ================= NAVBAR ================= --}}
<header class="lp-nav" id="lp-nav">
    <div class="lp-nav-in">
        <a class="lp-brand" href="#home" aria-label="CargoTrace">{!! $mark !!}<span>Cargo<b>Trace</b></span></a>
        <nav class="lp-links" aria-label="Main">
            <a href="#home" data-lp-link="home">Home</a>
            <a href="#why" data-lp-link="why">Why Us</a>
            <a href="#about" data-lp-link="about">About Us</a>
            <a href="#features" data-lp-link="features">Features</a>
            <a href="#how" data-lp-link="how">How It Works</a>
        </nav>
    </div>
</header>

<main class="lp">
    {{-- ================= HERO (gambar hero CargoTrace dipertahankan) ================= --}}
    <section id="home" class="lp-hero">
        <img class="lp-hero-img" src="{{ $heroUrl }}" alt="CargoTrace – global shipment visibility and tracking" fetchpriority="high">
        <div class="lp-hero-mobile">
            <div>
                <img src="{{ $heroUrl }}" alt="CargoTrace">
                <h1 data-i18n="tagline">Global shipment visibility & tracking</h1>
                <p data-i18n="loginSub">Track every shipment, anywhere in the world.</p>
                <button type="button" data-lp-open="login" class="lp-btn" data-i18n="signin">Sign in</button>
            </div>
        </div>
    </section>

    {{-- ================= MENGAPA MEMILIH CARGOTRACE (3 kolom x 2 baris) ================= --}}
    <section id="why" class="lp-section lp-why" aria-labelledby="why-title">
        <div class="lp-wrap">
            <div class="lp-head lp-reveal">
                <h2 id="why-title" class="lp-title"><span>Why Choose</span> <span class="lp-grad">CargoTrace</span><span>?</span></h2>
                <div class="lp-rule" aria-hidden="true"><i></i><i></i></div>
                <p class="lp-sub">Everything you need to follow every shipment from booking to delivery.</p>
            </div>
            <div class="lp-why-grid">
                @foreach($why as $i => [$icon, $title, $text])
                    <article class="lp-why-item lp-reveal" style="--lp-d: {{ ($i % 3) * .1 }}s">
                        <div class="lp-ico">{!! $svg($icon) !!}</div>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= TENTANG KAMI ================= --}}
    <section id="about" class="lp-section lp-about" aria-labelledby="about-title">
        <div class="lp-wrap lp-about-grid">
            <div class="lp-photo lp-reveal">
                <img src="{{ $img('about-courier.jpg') }}" alt="CargoTrace courier holding a parcel" loading="lazy">
            </div>
            <div class="lp-reveal" style="--lp-d: .12s">
                <span class="lp-eyebrow">About Us</span>
                <h2 id="about-title">Shipping you can see, from door to door</h2>
                <p>CargoTrace is a global shipment tracking platform that brings customers, logistics teams and administrators together in one clear workspace.</p>
                <p>We turn scattered shipping updates into a single live picture, so everyone knows where a package is, when it will arrive and what to do when something goes wrong.</p>
                <ul class="lp-checks">
                    <li>{!! $svg('check') !!}<span>One platform for customers, logistics staff and admins</span></li>
                    <li>{!! $svg('check') !!}<span>Live map with air, sea and road routes</span></li>
                    <li>{!! $svg('check') !!}<span>Available in 7 languages</span></li>
                </ul>
                <div class="lp-stats">
                    <div class="lp-stat"><strong>3</strong><span>Transport modes</span></div>
                    <div class="lp-stat"><strong>7</strong><span>Languages</span></div>
                    <div class="lp-stat"><strong>24/7</strong><span>Live tracking</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FITUR UNGGULAN ================= --}}
    <section id="features" class="lp-section lp-feat" aria-labelledby="feat-title">
        <div class="lp-wrap">
            <div class="lp-head lp-reveal">
                <span class="lp-eyebrow">Features</span>
                <h2 id="feat-title" class="lp-title">Key Features</h2>
                <div class="lp-rule" aria-hidden="true"><i></i><i></i></div>
                <p class="lp-sub">Powerful tools that keep every shipment moving and every customer informed.</p>
            </div>
            <div class="lp-feat-grid">
                <div class="lp-feat-list">
                    @foreach($feat as $i => [$icon, $title, $text])
                        <article class="lp-card lp-reveal" style="--lp-d: {{ $i * .1 }}s">
                            <div class="lp-ico">{!! $svg($icon) !!}</div>
                            <div><h3>{{ $title }}</h3><p>{{ $text }}</p></div>
                        </article>
                    @endforeach
                </div>
                <div class="lp-showcase lp-reveal" style="--lp-d: .15s">
                    <img src="{{ $img('feature-globe.jpg') }}" alt="CargoTrace globe with shipment routes" loading="lazy">
                    <span class="lp-float f1">{!! $svg('plane') !!}<span>Air freight</span></span>
                    <span class="lp-float f2">{!! $svg('ship') !!}<span>Sea freight</span></span>
                    <span class="lp-float f3">{!! $svg('truck') !!}<span>Road freight</span></span>
                </div>
            </div>
            <div class="lp-chips lp-reveal">
                @foreach($chips as $chip)<span>{{ $chip }}</span>@endforeach
            </div>
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section id="how" class="lp-section lp-how" aria-labelledby="how-title">
        <div class="lp-wrap">
            <div class="lp-head lp-reveal">
                <h2 id="how-title" class="lp-title">How It Works</h2>
                <div class="lp-rule" aria-hidden="true"><i></i><i></i></div>
                <p class="lp-sub">Four simple steps from sign-up to delivery.</p>
            </div>
            <ol class="lp-steps" style="list-style:none;margin:0;padding:0">
                @foreach($steps as $i => [$icon, $title, $text])
                    <li class="lp-step lp-reveal" style="--lp-d: {{ $i * .12 }}s">
                        <div class="lp-num">{!! $svg($icon) !!}<em aria-hidden="true">{{ $i + 1 }}</em></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="lp-cta" aria-labelledby="cta-title">
        <div class="lp-cta-box lp-reveal">
            <h2 id="cta-title">Ready to follow your shipment?</h2>
            <p>Sign in or create a customer account to start tracking today.</p>
            <div class="lp-cta-actions">
                <button type="button" class="lp-btn lp-btn-ghost" data-lp-open="login" data-i18n="signin">Sign in</button>
                <button type="button" class="lp-btn" data-lp-open="customer">Register as customer</button>
            </div>
        </div>
    </section>
</main>

{{-- ================= FOOTER ================= --}}
<footer class="lp-footer" id="footer">
    <div class="lp-foot-grid">
        <div>
            <a class="lp-brand" href="#home" aria-label="CargoTrace">{!! $mark !!}<span>Cargo<b>Trace</b></span></a>
            <p class="lp-foot-about">Global shipment visibility and tracking for customers, logistics teams and administrators.</p>
            <button type="button" class="lp-foot-contact" data-lp-open="contact">{!! $svg('headset') !!}<span data-i18n="Contact admin">Contact admin</span></button>
        </div>
        <div>
            <h4>Menu</h4>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#why">Why Us</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#how">How It Works</a></li>
            </ul>
        </div>
        <div>
            <h4>Get started</h4>
            <ul>
                <li><button type="button" data-lp-open="login" data-i18n="signin">Sign in</button></li>
                <li><button type="button" data-lp-open="customer">Register as customer</button></li>
                <li><button type="button" data-lp-open="operator">Register as logistics staff</button></li>
            </ul>
        </div>
        <div>
            <h4>Transport</h4>
            <ul>
                <li><span>Air freight</span></li>
                <li><span>Sea freight</span></li>
                <li><span>Road freight</span></li>
            </ul>
        </div>
    </div>
    <div class="lp-foot-bar">
        <span>© CargoTrace · Web Programming 2 UAS</span>
    </div>
</footer>

<button type="button" class="lp-top" id="lp-top" aria-label="Back to top">{!! $svg('up') !!}</button>
