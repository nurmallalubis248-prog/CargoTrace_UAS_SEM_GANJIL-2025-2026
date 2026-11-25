{{--
    Kontrol pojok kanan atas halaman tamu (login / daftar): menu "Daftar sebagai" (Customer / Logistik)
    dan pemilih bahasa. Pelanggan & Logistik membuka popup registrasi (Logistik dilindungi kode otorisasi, lihat
    AuthController::registerStaff). Dipakai oleh layouts/guest.blade.php.
--}}
@php
    // Popup registrasi. 'code' = butuh kode otorisasi (route register.staff); pelanggan memakai route register biasa.
    $staffRoles = [
        'customer' => [
            'icon' => 'user', 'code' => false,
            'title' => 'Register as customer',
            'note' => 'Create a customer account to book and track your shipments.',
            'button' => 'Create customer account',
        ],
        'operator' => [
            'icon' => 'truck', 'code' => true,
            'title' => 'Register as logistics staff',
            'note' => 'Logistics accounts require an authorization code from an administrator.',
            'button' => 'Create logistics account',
        ],
    ];
    $langs = ['id' => ['ID', 'Indonesia'], 'en' => ['EN', 'English'], 'ja' => ['JA', '日本語'], 'zh' => ['ZH', '中文'],
              'es' => ['ES', 'Español'], 'fr' => ['FR', 'Français'], 'ar' => ['AR', 'العربية']];
    $ico = [
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'chev' => '<path d="M6 9l6 6 6-6"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
        'truck' => '<path d="M1 6h13v10H1zM14 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'headset' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h2.5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4Zm16 0h-2.5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1H19a1 1 0 0 0 1-1v-4Z"/><path d="M18 19c0 1.5-1.5 2-4 2"/>',
        'wa' => '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3Z"/><path d="M9 8.5c.3 2.6 2.4 4.9 5.5 6 .6-.2 1.3-.9 1.3-1.5l-1.6-.9-.8.7c-1-.4-2-1.4-2.4-2.4l.7-.8-.9-1.6c-.6 0-1.4.7-1.8 1.5Z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'ig' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".8" fill="currentColor"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    ];
    // "Hubungi Admin": nilai dari config/contact.php (.env: CONTACT_WHATSAPP, CONTACT_PHONE, CONTACT_INSTAGRAM, CONTACT_EMAIL). Kosong = saluran disembunyikan.
    $ct = (array) config('contact');
    $waNum = (string) ($ct['whatsapp'] ?? '');
    $contactRows = array_values(array_filter([
        $waNum !== '' ? ['wa', 'WhatsApp', '+'.$waNum, 'https://wa.me/'.$waNum.'?text='.rawurlencode((string) ($ct['whatsapp_text'] ?? '')), true] : null,
        ! empty($ct['phone']) ? ['phone', 'Phone', (string) $ct['phone'], 'tel:'.preg_replace('/[^\d+]/', '', (string) $ct['phone']), false] : null,
        ! empty($ct['instagram']) ? ['ig', 'Instagram', '@'.$ct['instagram'], 'https://instagram.com/'.rawurlencode((string) $ct['instagram']), true] : null,
        ! empty($ct['email']) ? ['mail', 'Email', (string) $ct['email'], 'mailto:'.$ct['email'], false] : null,
    ]));
    $svg = fn (string $n, int $s = 18) => '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$ico[$n].'</svg>';
@endphp
<style>
    .ct-pill { position: fixed; top: 1.25rem; right: 1.25rem; z-index: 50; display: flex; align-items: center;
        border-radius: 9999px; background: rgba(255,255,255,.88); border: 1px solid rgba(255,255,255,.85);
        -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); box-shadow: 0 8px 24px -10px rgba(219,39,119,.35); }
    .ct-dd { position: relative; }
    .ct-pill-btn { display: flex; align-items: center; gap: .5rem; padding: .6rem 1rem; border-radius: 9999px;
        font-size: .8rem; font-weight: 600; color: #2B3027; cursor: pointer; background: transparent; border: 0; }
    .ct-pill-btn:hover { background: rgba(251,228,235,.7); }
    .ct-pill-btn .ct-ic { color: #e11d74; }
    .ct-pill-btn .ct-ic-dark { color: #2B3027; }
    .ct-pill-login { display: flex; align-items: center; gap: .45rem; margin: .25rem; padding: .5rem 1.1rem; border-radius: 9999px; border: 0; cursor: pointer;
        font-size: .8rem; font-weight: 700; color: #fff; background: linear-gradient(90deg, #e6458a, #f06aa5); box-shadow: 0 6px 16px -6px rgba(219,39,119,.6); }
    .ct-pill-login:hover { filter: brightness(1.06); }
    .ct-pill-sep { width: 1px; height: 1.4rem; background: #f0a5bc; }
    .ct-menu { position: absolute; top: calc(100% + .5rem); right: 0; min-width: 11.5rem; padding: .5rem; border-radius: 1rem;
        background: #fff; border: 1px solid #fbe4eb; box-shadow: 0 18px 40px -12px rgba(0,0,0,.28); }
    .ct-menu[hidden], .ct-modal[hidden] { display: none !important; }
    .ct-menu-title { padding: .35rem .7rem .45rem; font-size: .75rem; font-weight: 700; color: #565E4E; }
    .ct-menu-item { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .55rem .7rem; border-radius: .65rem;
        font-size: .85rem; font-weight: 600; color: #2B3027; text-align: left; background: transparent; border: 0; cursor: pointer; text-decoration: none; }
    .ct-menu-item:hover, .ct-menu-item:focus-visible, .ct-menu-item.is-on { background: #fdf2f5; color: #BF3F69; outline: none; }
    .ct-flag { display: inline-grid; place-items: center; width: 1.6rem; height: 1.1rem; border-radius: .25rem; background: #fbe4eb;
        font-size: .6rem; font-weight: 800; color: #BF3F69; letter-spacing: .02em; }
    .ct-modal { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .ct-modal-backdrop { position: absolute; inset: 0; background: rgba(38,51,26,.45); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
    .ct-modal-card { position: relative; width: 100%; max-width: 26rem; max-height: calc(100vh - 2rem); overflow-y: auto; padding: 1.75rem;
        border-radius: 1.5rem; background: #fff; box-shadow: 0 30px 70px -20px rgba(219,39,119,.45); }
    .ct-modal-x { position: absolute; top: .9rem; right: 1rem; width: 2rem; height: 2rem; border-radius: 9999px; border: 0; cursor: pointer;
        font-size: 1.4rem; line-height: 1; color: #737B69; background: transparent; }
    .ct-modal-x:hover { background: #fdf2f5; color: #BF3F69; }
    .ct-modal-ic { display: grid; place-items: center; width: 2.75rem; height: 2.75rem; border-radius: 9999px; background: #fbe4eb; color: #e11d74; }
    .ct-contact-row { display: flex; align-items: center; gap: .85rem; padding: .7rem .8rem; border-radius: 1rem; border: 1px solid #fbe4eb; text-decoration: none; color: #2B3027; transition: background .15s, border-color .15s; }
    .ct-contact-row + .ct-contact-row { margin-top: .6rem; }
    .ct-contact-row:hover, .ct-contact-row:focus-visible { background: #fdf2f5; border-color: #f0a5bc; outline: none; }
    .ct-contact-row .ci { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; flex: none; border-radius: 9999px; background: #fbe4eb; color: #e11d74; }
    .ct-contact-row small { display: block; font-size: .7rem; font-weight: 700; color: #BF3F69; }
    .ct-contact-row b { display: block; font-size: .95rem; font-weight: 700; word-break: break-word; }
    .ct-help-link { display: inline-flex; align-items: center; gap: .4rem; padding: 0; border: 0; background: transparent; cursor: pointer; font-size: .8rem; font-weight: 700; color: #BF3F69; }
    .ct-help-link:hover { text-decoration: underline; }
    @media (max-width: 639px) { .ct-pill { top: .75rem; right: .75rem; } .ct-pill-btn { padding: .55rem .7rem; } .ct-pill-label { display: none; } .ct-pill-login { padding: .5rem .9rem; } }
</style>

<div class="ct-pill" data-no-i18n-wrap>
    @guest
        <div class="ct-dd" data-dd>
            <button type="button" class="ct-pill-btn" data-dd-btn aria-haspopup="menu" aria-expanded="false" aria-label="Register as">
                <span class="ct-ic">{!! $svg('user') !!}</span><span class="ct-pill-label">User</span><span class="ct-ic-dark">{!! $svg('chev', 14) !!}</span>
            </button>
            <div class="ct-menu" role="menu" hidden>
                <p class="ct-menu-title">Register as:</p>
                <button type="button" role="menuitem" class="ct-menu-item" data-open-reg="customer"><span style="color:#e11d74">{!! $svg('user') !!}</span>Customer</button>
                <button type="button" role="menuitem" class="ct-menu-item" data-open-reg="operator"><span style="color:#e11d74">{!! $svg('truck') !!}</span>Logistics</button>
            </div>
        </div>
        <span class="ct-pill-sep" aria-hidden="true"></span>
    @endguest
    <div class="ct-dd" data-dd>
        <button type="button" class="ct-pill-btn" data-dd-btn aria-haspopup="menu" aria-expanded="false" aria-label="Language">
            <span class="ct-ic-dark">{!! $svg('globe') !!}</span><span class="ct-pill-label" data-i18n="language">Language</span><span class="ct-ic-dark">{!! $svg('chev', 14) !!}</span>
        </button>
        <div class="ct-menu" role="menu" hidden data-no-i18n>
            @foreach($langs as $code => [$badge, $label])
                <button type="button" role="menuitem" class="ct-menu-item" data-set-lang="{{ $code }}"><span class="ct-flag">{{ $badge }}</span>{{ $label }}</button>
            @endforeach
        </div>
    </div>
    @guest
        <button type="button" class="ct-pill-login" data-open-reg="login">{!! $svg('login', 16) !!}<span data-i18n="signin">Sign in</span></button>
    @endguest
</div>

@guest
{{-- Popup "Masuk". Galat login memakai bag default + flash 'login_failed' (lihat AuthController::login) sehingga popup terbuka lagi. --}}
@php $loginFailed = session('login_failed') && $errors->any(); @endphp
<div id="reg-modal-login" class="ct-modal" data-reg-modal @if($loginFailed) data-auto-open @else hidden @endif
     role="dialog" aria-modal="true" aria-labelledby="reg-title-login">
    <div class="ct-modal-backdrop" data-close-reg></div>
    <div class="ct-modal-card">
        <button type="button" class="ct-modal-x" data-close-reg aria-label="Close">&times;</button>
        <div class="ct-modal-ic">{!! $svg('login', 22) !!}</div>
        <h3 id="reg-title-login" class="mt-3 text-2xl font-extrabold tracking-tight text-matcha-900" data-i18n="loginTitle">Sign in to your account</h3>
        <p class="mb-5 mt-1 text-sm text-ink-500" data-i18n="loginSub">Track every shipment, anywhere in the world.</p>
        @if($loginFailed)
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="login-email" data-i18n="email">Email</label>
                <input class="input !py-3" id="login-email" type="email" name="email" value="{{ $loginFailed ? old('email') : '' }}" placeholder="name@company.com" autocomplete="username" required>
            </div>
            <div>
                <label class="label" for="login-password" data-i18n="password">Password</label>
                <input class="input !py-3" id="login-password" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-matcha-600"> <span data-i18n="remember">Remember me</span></label>
            <button class="grad-brand w-full rounded-xl py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90" type="submit" data-i18n="signin">Sign in</button>
        </form>
        <p class="mt-4 text-center"><button type="button" class="ct-help-link" data-open-reg="contact">{!! $svg('headset', 16) !!}<span>Need help? Contact admin</span></button></p>
        @if(config('cargotrace.show_demo_accounts'))
            <div class="mt-5 rounded-xl border border-matcha-100 bg-matcha-50 p-4 text-xs text-ink-600">
                <p class="mb-1 font-bold text-matcha-800"><span data-i18n="demo">Demo accounts</span> (password: <code>password</code>)</p>
                <p>admin@cargotrace.test · operator@cargotrace.test · customer1@cargotrace.test</p>
            </div>
        @endif
    </div>
</div>

{{-- Popup "Hubungi Admin" --}}
<div id="reg-modal-contact" class="ct-modal" data-reg-modal hidden role="dialog" aria-modal="true" aria-labelledby="reg-title-contact">
    <div class="ct-modal-backdrop" data-close-reg></div>
    <div class="ct-modal-card">
        <button type="button" class="ct-modal-x" data-close-reg aria-label="Close">&times;</button>
        <div class="ct-modal-ic">{!! $svg('headset', 22) !!}</div>
        <h3 id="reg-title-contact" class="mt-3 text-xl font-extrabold tracking-tight text-matcha-900">Contact admin</h3>
        <p class="mb-4 mt-1 text-xs text-ink-500">Forgot your password, need an account or authorization code? Reach the admin through any channel below.</p>
        @forelse($contactRows as [$cIcon, $cLabel, $cValue, $cHref, $cBlank])
            <a class="ct-contact-row" href="{{ $cHref }}" @if($cBlank) target="_blank" rel="noopener noreferrer" @endif>
                <span class="ci">{!! $svg($cIcon, 20) !!}</span>
                <span><small>{{ $cLabel }}</small><b data-no-i18n>{{ $cValue }}</b></span>
            </a>
        @empty
            <p class="rounded-xl border border-matcha-100 bg-matcha-50 p-4 text-sm text-ink-600">The admin contact has not been set up yet.</p>
        @endforelse
    </div>
</div>

@foreach($staffRoles as $role => $meta)
    @php
        if ($meta['code']) { $errs = $errors->getBag('staff_'.$role)->all(); $open = ! empty($errs); }
        else { $errs = session('register_failed') ? $errors->all() : []; $open = ! empty($errs) || request()->routeIs('register'); }   // pelanggan: /register langsung membuka popup
        $failed = ! empty($errs);
    @endphp
    <div id="reg-modal-{{ $role }}" class="ct-modal" data-reg-modal @if($open) data-auto-open @else hidden @endif
         role="dialog" aria-modal="true" aria-labelledby="reg-title-{{ $role }}">
        <div class="ct-modal-backdrop" data-close-reg></div>
        <div class="ct-modal-card">
            <button type="button" class="ct-modal-x" data-close-reg aria-label="Close">&times;</button>
            <div class="ct-modal-ic">{!! $svg($meta['icon'], 22) !!}</div>
            <h3 id="reg-title-{{ $role }}" class="mt-3 text-xl font-extrabold tracking-tight text-matcha-900">{{ $meta['title'] }}</h3>
            <p class="mb-4 mt-1 text-xs text-ink-500">{{ $meta['note'] }}</p>

            @if($failed)
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                    <ul class="list-disc pl-4">@foreach($errs as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ $meta['code'] ? route('register.staff', $role) : route('register') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="{{ $role }}-name">Full name</label>
                    <input class="input" id="{{ $role }}-name" type="text" name="name" value="{{ $failed ? old('name') : '' }}" autocomplete="name" required>
                </div>
                <div>
                    <label class="label" for="{{ $role }}-email">Email</label>
                    <input class="input" id="{{ $role }}-email" type="email" name="email" value="{{ $failed ? old('email') : '' }}" autocomplete="email" required>
                </div>
                <div>
                    <label class="label" for="{{ $role }}-password">Password (min. 8 characters)</label>
                    <input class="input" id="{{ $role }}-password" type="password" name="password" autocomplete="new-password" required>
                </div>
                <div>
                    <label class="label" for="{{ $role }}-password2">Confirm password</label>
                    <input class="input" id="{{ $role }}-password2" type="password" name="password_confirmation" autocomplete="new-password" required>
                </div>
                @if($meta['code'])
                <div>
                    <label class="label" for="{{ $role }}-code">Authorization code</label>
                    <input class="input" id="{{ $role }}-code" type="password" name="access_code" autocomplete="off" required>
                </div>
                @endif
                <button class="grad-brand w-full rounded-xl py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90" type="submit">{{ $meta['button'] }}</button>
            </form>
        </div>
    </div>
@endforeach
@endguest

<script>
(function () {
    var dds = Array.prototype.slice.call(document.querySelectorAll('[data-dd]'));
    function closeMenus(except) {
        dds.forEach(function (d) {
            if (d === except) { return; }
            d.querySelector('.ct-menu').hidden = true;
            d.querySelector('[data-dd-btn]').setAttribute('aria-expanded', 'false');
        });
    }
    dds.forEach(function (d) {
        var b = d.querySelector('[data-dd-btn]'), m = d.querySelector('.ct-menu');
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = m.hidden; closeMenus(d); m.hidden = !open; b.setAttribute('aria-expanded', String(open));
            if (open) { var f = m.querySelector('[role=menuitem]'); if (f) { f.focus(); } }
        });
        m.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') { return; }
            e.preventDefault();
            var items = Array.prototype.slice.call(m.querySelectorAll('[role=menuitem]')), i = items.indexOf(document.activeElement);
            items[(i + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length].focus();
        });
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('[data-dd]')) { closeMenus(); } });

    /* Popup registrasi */
    var trigger = null;
    function openModal(role, from) {
        var m = document.getElementById('reg-modal-' + role); if (!m) { return; }
        closeMenus(); trigger = from || null; m.hidden = false; document.body.style.overflow = 'hidden';
        var f = m.querySelector('input'); if (f) { f.focus(); }
    }
    function closeModals() {
        document.querySelectorAll('[data-reg-modal]').forEach(function (m) { m.hidden = true; m.removeAttribute('data-auto-open'); });
        document.body.style.overflow = ''; if (trigger) { trigger.focus(); trigger = null; }
    }
    document.querySelectorAll('[data-open-reg]').forEach(function (el) { el.addEventListener('click', function () { openModal(el.getAttribute('data-open-reg'), el); }); });
    document.querySelectorAll('[data-close-reg]').forEach(function (el) { el.addEventListener('click', closeModals); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeMenus(); if (document.querySelector('[data-reg-modal]:not([hidden])')) { closeModals(); } return; }
        if (e.key !== 'Tab') { return; }
        var m = document.querySelector('[data-reg-modal]:not([hidden])'); if (!m) { return; }   /* fokus tetap di dalam popup */
        var f = m.querySelectorAll('button, a[href], input, select, textarea'); if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    var auto = document.querySelector('[data-reg-modal][data-auto-open]');   /* validasi gagal -> popup (login / customer / logistik) terbuka lagi dengan pesan galat */
    if (auto) { document.body.style.overflow = 'hidden'; var bad = auto.querySelector('input[name=access_code]') || (auto.querySelector('.text-red-800') ? auto.querySelector('input[name=password]') : auto.querySelector('input')); if (bad) { bad.focus(); } }

    /* Bahasa */
    document.querySelectorAll('[data-set-lang]').forEach(function (el) {
        el.addEventListener('click', function () { if (window.CT) { CT.setLang(el.getAttribute('data-set-lang')); } closeMenus(); });
    });
    document.addEventListener('ct:lang', function (e) {
        document.querySelectorAll('[data-set-lang]').forEach(function (el) { el.classList.toggle('is-on', el.getAttribute('data-set-lang') === e.detail); });
    });
})();
</script>
