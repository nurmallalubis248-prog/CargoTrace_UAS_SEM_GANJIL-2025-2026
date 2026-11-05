/* CargoTrace i18n: EN <-> ID. Elements use data-i18n="key"; placeholders use data-i18n-ph="key". */
(function () {
  const D = {
    en: { dashboard:'Dashboard', shipments:'Shipments', carriers:'Carriers', locations:'Locations', users:'Users', audit:'Audit log',
      notifications:'Notifications', newShipment:'New shipment', logout:'Log out', menu:'Main menu', admin:'Administration',
      total:'Total shipments', transit:'In transit', delivered:'Delivered', delayed:'Delayed', exceptions:'Open exceptions', delayRate:'Delay rate',
      excRate:'Exception rate', onTime:'On-time delivery rate', avg:'Avg delay · Avg exception resolution',
      byStatus:'Shipments by status', byCarrier:'Shipments by carrier', bySeverity:'Delays by severity',
      liveMap:'Live shipment map', liveMapSub:'Sea, air and road shipments — latest known position', clock:'Local time', calendar:'Calendar',
      yourShip:'Your shipments', allShip:'All shipments', welcome:'Welcome back', modeSea:'Sea', modeAir:'Air', modeRoad:'Road',
      loginTitle:'Sign in to your account', loginSub:'Track every shipment, anywhere in the world.', email:'Email', password:'Password', remember:'Remember me',
      signin:'Sign in', noAccount:'No account?', register:'Register as customer', demo:'Demo accounts', tagline:'Global shipment visibility & tracking',
      f1:'Live world map with sea, air & road routes', f2:'Automatic ETA & delay detection', f3:'Exception management & audit trail',
      country:'Country', allCountries:'All countries', language:'Language', revenue:'Revenue received', origin:'Origin', destination:'Destination', loadErr:'Could not load dashboard data. Please refresh the page.', noPos:'No active shipments with a known position.' },
    id: { dashboard:'Dasbor', shipments:'Pengiriman', carriers:'Pengangkut', locations:'Lokasi', users:'Pengguna', audit:'Log audit',
      notifications:'Notifikasi', newShipment:'Pengiriman baru', logout:'Keluar', menu:'Menu utama', admin:'Administrasi',
      total:'Total pengiriman', transit:'Dalam perjalanan', delivered:'Terkirim', delayed:'Terlambat', exceptions:'Eksepsi terbuka', delayRate:'Tingkat keterlambatan',
      excRate:'Tingkat eksepsi', onTime:'Tingkat ketepatan waktu', avg:'Rata-rata keterlambatan · Rata-rata penyelesaian eksepsi',
      byStatus:'Pengiriman per status', byCarrier:'Pengiriman per pengangkut', bySeverity:'Keterlambatan per tingkat',
      liveMap:'Peta pengiriman langsung', liveMapSub:'Pengiriman laut, udara, dan darat — posisi terakhir yang diketahui', clock:'Waktu lokal', calendar:'Kalender',
      yourShip:'Pengiriman Anda', allShip:'Semua pengiriman', welcome:'Selamat datang kembali', modeSea:'Laut', modeAir:'Udara', modeRoad:'Darat',
      loginTitle:'Masuk ke akun Anda', loginSub:'Lacak setiap pengiriman di seluruh dunia.', email:'Email', password:'Kata sandi', remember:'Ingat saya',
      signin:'Masuk', noAccount:'Belum punya akun?', register:'Daftar sebagai pelanggan', demo:'Akun demo', tagline:'Visibilitas & pelacakan pengiriman global',
      f1:'Peta dunia langsung dengan rute laut, udara & darat', f2:'Deteksi ETA & keterlambatan otomatis', f3:'Manajemen eksepsi & jejak audit',
      country:'Negara', allCountries:'Semua negara', language:'Bahasa', revenue:'Pendapatan diterima', origin:'Asal', destination:'Tujuan', loadErr:'Data dasbor gagal dimuat. Silakan muat ulang halaman.', noPos:'Tidak ada pengiriman aktif dengan posisi yang diketahui.' },
    zh: {"dashboard": "仪表板", "shipments": "货运", "carriers": "承运商", "locations": "地点", "users": "用户", "audit": "审计日志", "notifications": "通知", "newShipment": "新建货运", "logout": "退出登录", "menu": "主菜单", "admin": "管理", "total": "货运总数", "transit": "运输中", "delivered": "已送达", "delayed": "已延误", "exceptions": "未处理异常", "delayRate": "延误率", "excRate": "异常率", "onTime": "准时送达率", "avg": "平均延误 · 平均异常处理时间", "byStatus": "按状态统计", "byCarrier": "按承运商统计", "bySeverity": "按严重程度统计的延误", "liveMap": "实时货运地图", "liveMapSub": "海运、空运和公路运输——最新已知位置", "clock": "当地时间", "calendar": "日历", "yourShip": "您的货运", "allShip": "全部货运", "welcome": "欢迎回来", "modeSea": "海运", "modeAir": "空运", "modeRoad": "公路", "loginTitle": "登录您的账户", "loginSub": "随时随地追踪每一票货物。", "email": "电子邮箱", "password": "密码", "remember": "记住我", "signin": "登录", "noAccount": "没有账户？", "register": "注册为客户", "demo": "演示账户", "tagline": "全球货运可视化与追踪", "f1": "实时世界地图，含海运、空运与公路路线", "f2": "自动预计到达时间与延误检测", "f3": "异常管理与审计追踪", "revenue": "已收款项", "origin": "起点", "destination": "终点", "country": "国家/地区", "allCountries": "所有国家/地区", "language": "语言"},
    ja: {"dashboard": "ダッシュボード", "shipments": "貨物", "carriers": "運送会社", "locations": "拠点", "users": "ユーザー", "audit": "監査ログ", "notifications": "通知", "newShipment": "新規貨物", "logout": "ログアウト", "menu": "メインメニュー", "admin": "管理", "total": "貨物総数", "transit": "輸送中", "delivered": "配達済み", "delayed": "遅延", "exceptions": "未解決の例外", "delayRate": "遅延率", "excRate": "例外率", "onTime": "定時配達率", "avg": "平均遅延 · 平均例外解決時間", "byStatus": "ステータス別", "byCarrier": "運送会社別", "bySeverity": "重大度別の遅延", "liveMap": "リアルタイム貨物マップ", "liveMapSub": "海上・航空・陸上輸送の最新位置", "clock": "現地時間", "calendar": "カレンダー", "yourShip": "あなたの貨物", "allShip": "すべての貨物", "welcome": "おかえりなさい", "modeSea": "海上", "modeAir": "航空", "modeRoad": "陸上", "loginTitle": "アカウントにログイン", "loginSub": "世界中のすべての貨物を追跡。", "email": "メール", "password": "パスワード", "remember": "ログイン状態を保持", "signin": "ログイン", "noAccount": "アカウントをお持ちでない方", "register": "顧客として登録", "demo": "デモアカウント", "tagline": "グローバル貨物の可視化と追跡", "f1": "海・空・陸のルートを表示するライブ世界地図", "f2": "ETAと遅延の自動検出", "f3": "例外管理と監査証跡", "revenue": "入金額", "origin": "出発地", "destination": "目的地", "country": "国・地域", "allCountries": "すべての国・地域", "language": "言語"},
    es: {"dashboard": "Panel", "shipments": "Envíos", "carriers": "Transportistas", "locations": "Ubicaciones", "users": "Usuarios", "audit": "Registro de auditoría", "notifications": "Notificaciones", "newShipment": "Nuevo envío", "logout": "Cerrar sesión", "menu": "Menú principal", "admin": "Administración", "total": "Envíos totales", "transit": "En tránsito", "delivered": "Entregados", "delayed": "Retrasados", "exceptions": "Excepciones abiertas", "delayRate": "Tasa de retraso", "excRate": "Tasa de excepciones", "onTime": "Entregas a tiempo", "avg": "Retraso medio · Resolución media de excepciones", "byStatus": "Envíos por estado", "byCarrier": "Envíos por transportista", "bySeverity": "Retrasos por gravedad", "liveMap": "Mapa de envíos en vivo", "liveMapSub": "Marítimo, aéreo y terrestre: última posición conocida", "clock": "Hora local", "calendar": "Calendario", "yourShip": "Sus envíos", "allShip": "Todos los envíos", "welcome": "Bienvenido de nuevo", "modeSea": "Mar", "modeAir": "Aire", "modeRoad": "Tierra", "loginTitle": "Inicie sesión en su cuenta", "loginSub": "Siga cada envío, en cualquier parte del mundo.", "email": "Correo electrónico", "password": "Contraseña", "remember": "Recordarme", "signin": "Iniciar sesión", "noAccount": "¿No tiene cuenta?", "register": "Registrarse como cliente", "demo": "Cuentas de demostración", "tagline": "Visibilidad y seguimiento global de envíos", "f1": "Mapa mundial en vivo con rutas marítimas, aéreas y terrestres", "f2": "Detección automática de ETA y retrasos", "f3": "Gestión de excepciones y auditoría", "revenue": "Ingresos recibidos", "origin": "Origen", "destination": "Destino", "country": "País", "allCountries": "Todos los países", "language": "Idioma"},
    fr: {"dashboard": "Tableau de bord", "shipments": "Expéditions", "carriers": "Transporteurs", "locations": "Lieux", "users": "Utilisateurs", "audit": "Journal d'audit", "notifications": "Notifications", "newShipment": "Nouvelle expédition", "logout": "Déconnexion", "menu": "Menu principal", "admin": "Administration", "total": "Expéditions totales", "transit": "En transit", "delivered": "Livrées", "delayed": "En retard", "exceptions": "Exceptions ouvertes", "delayRate": "Taux de retard", "excRate": "Taux d'exceptions", "onTime": "Livraisons à l'heure", "avg": "Retard moyen · Résolution moyenne des exceptions", "byStatus": "Expéditions par statut", "byCarrier": "Expéditions par transporteur", "bySeverity": "Retards par gravité", "liveMap": "Carte des expéditions en direct", "liveMapSub": "Maritime, aérien et routier : dernière position connue", "clock": "Heure locale", "calendar": "Calendrier", "yourShip": "Vos expéditions", "allShip": "Toutes les expéditions", "welcome": "Bon retour", "modeSea": "Mer", "modeAir": "Air", "modeRoad": "Route", "loginTitle": "Connectez-vous à votre compte", "loginSub": "Suivez chaque expédition, partout dans le monde.", "email": "E-mail", "password": "Mot de passe", "remember": "Se souvenir de moi", "signin": "Se connecter", "noAccount": "Pas de compte ?", "register": "S'inscrire comme client", "demo": "Comptes de démonstration", "tagline": "Visibilité et suivi mondial des expéditions", "f1": "Carte du monde en direct : routes maritimes, aériennes et routières", "f2": "Détection automatique de l'ETA et des retards", "f3": "Gestion des exceptions et piste d'audit", "revenue": "Revenus encaissés", "origin": "Origine", "destination": "Destination", "country": "Pays", "allCountries": "Tous les pays", "language": "Langue"},
    ar: {"dashboard": "لوحة التحكم", "shipments": "الشحنات", "carriers": "الناقلون", "locations": "المواقع", "users": "المستخدمون", "audit": "سجل التدقيق", "notifications": "الإشعارات", "newShipment": "شحنة جديدة", "logout": "تسجيل الخروج", "menu": "القائمة الرئيسية", "admin": "الإدارة", "total": "إجمالي الشحنات", "transit": "قيد النقل", "delivered": "تم التسليم", "delayed": "متأخرة", "exceptions": "الاستثناءات المفتوحة", "delayRate": "معدل التأخير", "excRate": "معدل الاستثناءات", "onTime": "معدل التسليم في الوقت", "avg": "متوسط التأخير · متوسط حل الاستثناءات", "byStatus": "الشحنات حسب الحالة", "byCarrier": "الشحنات حسب الناقل", "bySeverity": "التأخيرات حسب الخطورة", "liveMap": "خريطة الشحنات المباشرة", "liveMapSub": "بحري وجوي وبري — آخر موقع معروف", "clock": "الوقت المحلي", "calendar": "التقويم", "yourShip": "شحناتك", "allShip": "جميع الشحنات", "welcome": "مرحبًا بعودتك", "modeSea": "بحري", "modeAir": "جوي", "modeRoad": "بري", "loginTitle": "سجّل الدخول إلى حسابك", "loginSub": "تتبّع كل شحنة في أي مكان في العالم.", "email": "البريد الإلكتروني", "password": "كلمة المرور", "remember": "تذكّرني", "signin": "تسجيل الدخول", "noAccount": "ليس لديك حساب؟", "register": "التسجيل كعميل", "demo": "حسابات تجريبية", "tagline": "رؤية وتتبّع الشحنات عالميًا", "f1": "خريطة عالمية مباشرة بمسارات بحرية وجوية وبرية", "f2": "اكتشاف تلقائي لوقت الوصول والتأخير", "f3": "إدارة الاستثناءات وسجل التدقيق", "revenue": "الإيرادات المستلمة", "origin": "المنشأ", "destination": "الوجهة", "country": "الدولة", "allCountries": "كل الدول", "language": "اللغة"}
  };
  const KEY = 'ct_lang', CKEY = 'ct_country';
  const SUPPORTED = ['id', 'en', 'zh', 'ja', 'es', 'fr', 'ar'];
  const NAMES = { id: 'Bahasa Indonesia', en: 'English', zh: '简体中文', ja: '日本語', es: 'Español', fr: 'Français', ar: 'العربية' };
  const CC2LANG = {};
  const put = (l, codes) => codes.split(' ').forEach((c) => { CC2LANG[c] = l; });
  put('zh', 'CN TW HK MO'); put('ja', 'JP'); put('id', 'ID');
  put('es', 'ES MX AR CO CL PE VE EC GT CU BO DO HN PY SV NI CR PA UY GQ');
  put('fr', 'FR BE LU MC SN CI CM CD MG ML BF NE TG BJ GA HT');
  put('ar', 'SA AE EG IQ MA DZ JO KW LB LY OM QA SD SY TN YE BH PS');
  const CODES = 'AF AL DZ AD AO AG AR AM AU AT AZ BS BH BD BB BY BE BZ BJ BT BO BA BW BR BN BG BF BI KH CM CA CV CF TD CL CN CO KM CG CD CR CI HR CU CY CZ DK DJ DM DO EC EG SV GQ ER EE SZ ET FJ FI FR GA GM GE DE GH GR GD GT GN GW GY HT HN HK HU IS IN ID IR IQ IE IL IT JM JP JO KZ KE KI KP KR KW KG LA LV LB LS LR LY LI LT LU MO MG MW MY MV ML MT MH MR MU MX FM MD MC MN ME MA MZ MM NA NR NP NL NZ NI NE NG MK NO OM PK PW PS PA PG PY PE PH PL PT QA RO RU RW KN LC VC WS SM ST SA SN RS SC SL SG SK SI SB SO ZA SS LK SD SR SE CH SY TW TJ TZ TH TL TG TO TT TN TR TM TV UG UA GB US UZ VU VA VE VN YE ZM ZW ES AE'.split(' ');
  /* ---- Zona waktu per negara: jam & kalender mengikuti negara yang dipilih (zona utama / ibu kota). Tanpa pilihan negara = zona waktu perangkat. ---- */
  const TZ = {};
  'AF Asia/Kabul|AL Europe/Tirane|DZ Africa/Algiers|AD Europe/Andorra|AO Africa/Luanda|AG America/Antigua|AR America/Argentina/Buenos_Aires|AM Asia/Yerevan|AU Australia/Sydney|AT Europe/Vienna|AZ Asia/Baku|BS America/Nassau|BH Asia/Bahrain|BD Asia/Dhaka|BB America/Barbados|BY Europe/Minsk|BE Europe/Brussels|BZ America/Belize|BJ Africa/Porto-Novo|BT Asia/Thimphu|BO America/La_Paz|BA Europe/Sarajevo|BW Africa/Gaborone|BR America/Sao_Paulo|BN Asia/Brunei|BG Europe/Sofia|BF Africa/Ouagadougou|BI Africa/Bujumbura|KH Asia/Phnom_Penh|CM Africa/Douala|CA America/Toronto|CV Atlantic/Cape_Verde|CF Africa/Bangui|TD Africa/Ndjamena|CL America/Santiago|CN Asia/Shanghai|CO America/Bogota|KM Indian/Comoro|CG Africa/Brazzaville|CD Africa/Kinshasa|CR America/Costa_Rica|CI Africa/Abidjan|HR Europe/Zagreb|CU America/Havana|CY Asia/Nicosia|CZ Europe/Prague|DK Europe/Copenhagen|DJ Africa/Djibouti|DM America/Dominica|DO America/Santo_Domingo|EC America/Guayaquil|EG Africa/Cairo|SV America/El_Salvador|GQ Africa/Malabo|ER Africa/Asmara|EE Europe/Tallinn|SZ Africa/Mbabane|ET Africa/Addis_Ababa|FJ Pacific/Fiji|FI Europe/Helsinki|FR Europe/Paris|GA Africa/Libreville|GM Africa/Banjul|GE Asia/Tbilisi|DE Europe/Berlin|GH Africa/Accra|GR Europe/Athens|GD America/Grenada|GT America/Guatemala|GN Africa/Conakry|GW Africa/Bissau|GY America/Guyana|HT America/Port-au-Prince|HN America/Tegucigalpa|HK Asia/Hong_Kong|HU Europe/Budapest|IS Atlantic/Reykjavik|IN Asia/Kolkata|ID Asia/Jakarta|IR Asia/Tehran|IQ Asia/Baghdad|IE Europe/Dublin|IL Asia/Jerusalem|IT Europe/Rome|JM America/Jamaica|JP Asia/Tokyo|JO Asia/Amman|KZ Asia/Almaty|KE Africa/Nairobi|KI Pacific/Tarawa|KP Asia/Pyongyang|KR Asia/Seoul|KW Asia/Kuwait|KG Asia/Bishkek|LA Asia/Vientiane|LV Europe/Riga|LB Asia/Beirut|LS Africa/Maseru|LR Africa/Monrovia|LY Africa/Tripoli|LI Europe/Vaduz|LT Europe/Vilnius|LU Europe/Luxembourg|MO Asia/Macau|MG Indian/Antananarivo|MW Africa/Blantyre|MY Asia/Kuala_Lumpur|MV Indian/Maldives|ML Africa/Bamako|MT Europe/Malta|MH Pacific/Majuro|MR Africa/Nouakchott|MU Indian/Mauritius|MX America/Mexico_City|FM Pacific/Pohnpei|MD Europe/Chisinau|MC Europe/Monaco|MN Asia/Ulaanbaatar|ME Europe/Podgorica|MA Africa/Casablanca|MZ Africa/Maputo|MM Asia/Yangon|NA Africa/Windhoek|NR Pacific/Nauru|NP Asia/Kathmandu|NL Europe/Amsterdam|NZ Pacific/Auckland|NI America/Managua|NE Africa/Niamey|NG Africa/Lagos|MK Europe/Skopje|NO Europe/Oslo|OM Asia/Muscat|PK Asia/Karachi|PW Pacific/Palau|PS Asia/Gaza|PA America/Panama|PG Pacific/Port_Moresby|PY America/Asuncion|PE America/Lima|PH Asia/Manila|PL Europe/Warsaw|PT Europe/Lisbon|QA Asia/Qatar|RO Europe/Bucharest|RU Europe/Moscow|RW Africa/Kigali|KN America/St_Kitts|LC America/St_Lucia|VC America/St_Vincent|WS Pacific/Apia|SM Europe/San_Marino|ST Africa/Sao_Tome|SA Asia/Riyadh|SN Africa/Dakar|RS Europe/Belgrade|SC Indian/Mahe|SL Africa/Freetown|SG Asia/Singapore|SK Europe/Bratislava|SI Europe/Ljubljana|SB Pacific/Guadalcanal|SO Africa/Mogadishu|ZA Africa/Johannesburg|SS Africa/Juba|LK Asia/Colombo|SD Africa/Khartoum|SR America/Paramaribo|SE Europe/Stockholm|CH Europe/Zurich|SY Asia/Damascus|TW Asia/Taipei|TJ Asia/Dushanbe|TZ Africa/Dar_es_Salaam|TH Asia/Bangkok|TL Asia/Dili|TG Africa/Lome|TO Pacific/Tongatapu|TT America/Port_of_Spain|TN Africa/Tunis|TR Europe/Istanbul|TM Asia/Ashgabat|TV Pacific/Funafuti|UG Africa/Kampala|UA Europe/Kyiv|GB Europe/London|US America/New_York|UZ Asia/Tashkent|VU Pacific/Efate|VA Europe/Vatican|VE America/Caracas|VN Asia/Ho_Chi_Minh|YE Asia/Aden|ZM Africa/Lusaka|ZW Africa/Harare|ES Europe/Madrid|AE Asia/Dubai'.split('|').forEach((x) => { const i = x.indexOf(' '); TZ[x.slice(0, i)] = x.slice(i + 1); });
  const validTz = (z) => { try { new Intl.DateTimeFormat('en', { timeZone: z }); return z; } catch (e) { return undefined; } };
  const store = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
  const load = (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } };
  const detect = () => { const b = (navigator.language || 'id').slice(0, 2).toLowerCase(); return SUPPORTED.includes(b) ? b : 'id'; };
  const get = () => { const l = load(KEY); return SUPPORTED.includes(l) ? l : detect(); };
  const tr = (l, k) => (D[l] && D[l][k]) || D.en[k] || k;
  function fillCountries() {
    const sel = document.getElementById('country-sel'); if (!sel) { return; }
    const l = get(), cur = load(CKEY) || '';
    let dn = null; try { dn = new Intl.DisplayNames([l], { type: 'region' }); } catch (e) {}
    const list = [...new Set(CODES)].map((c) => [c, dn ? dn.of(c) : c]).sort((a, b) => a[1].localeCompare(b[1], l));
    sel.innerHTML = '<option value="">' + tr(l, 'allCountries') + '</option>' + list.map((x) => '<option value="' + x[0] + '">' + x[1] + '</option>').join('');
    sel.value = cur;
  }

  /* ---- Full-page translation engine: translates every text node / placeholder using CT.DICT (original English kept in memory) ---- */
  const LI = { id: 1, zh: 2, ja: 3, es: 4, fr: 5, ar: 6 };
  const LOCALE = { id: 'id-ID', en: 'en-GB', zh: 'zh-CN', ja: 'ja-JP', es: 'es-ES', fr: 'fr-FR', ar: 'ar-SA' };
  const norm = (s) => s.replace(/[_\s]+/g, ' ').trim().toLowerCase();
  let IDX = null, PATS = [];
  function build() {
    IDX = new Map(); PATS = [];
    (window.CT.DICT || '').split('\n').forEach((line) => {
      if (!line.trim()) { return; }
      const row = line.split('|'), en = row[0].trim();
      if (/\{\d\}/.test(en)) {
        PATS.push({ re: new RegExp('^' + en.replace(/[.*+?^$()|[\]\\]/g, '\\$&').replace(/\{\d\}/g, '(.+?)') + '$'), row: row });
      } else { IDX.set(norm(en), row); }
    });
  }
  function lookup(l, text) {
    const i = LI[l]; if (!i || !IDX) { return null; }
    const t = text.trim(); if (!t) { return null; }
    let r = IDX.get(norm(t)); if (r && r[i]) { return r[i].trim(); }
    const m = t.match(/^(.*?)(\s*[*:])$/);
    if (m) { r = IDX.get(norm(m[1])); if (r && r[i]) { return r[i].trim() + m[2]; } }
    for (const p of PATS) {
      const mm = p.re.exec(t);
      if (mm && p.row[i]) { return p.row[i].trim().replace(/\{(\d)\}/g, (_, n) => { const a = mm[+n + 1]; return lookup(l, a) || a; }); }
    }
    return null;
  }
  const SKIP = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, CODE: 1, PRE: 1, NOSCRIPT: 1 };
  function walk(root) {
    const l = get();
    const tw = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, { acceptNode: (n) => {
      const p = n.parentElement;
      return (!p || SKIP[p.tagName] || p.closest('[data-i18n],[data-no-i18n],[data-idr]')) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT; } });
    const nodes = []; while (tw.nextNode()) { nodes.push(tw.currentNode); }
    nodes.forEach((n) => {
      if (n.__o === undefined) { if (!n.nodeValue.trim()) { return; } n.__o = n.nodeValue; n.__l = n.nodeValue; }
      else if (n.nodeValue !== n.__l) { n.__o = n.nodeValue; }
      const tr = l === 'en' ? null : lookup(l, n.__o);
      const out = tr ? n.__o.match(/^\s*/)[0] + tr + n.__o.match(/\s*$/)[0] : n.__o;
      if (n.nodeValue !== out) { n.nodeValue = out; }
      n.__l = out;
    });
    root.querySelectorAll('[placeholder],[title],[aria-label]').forEach((el) => {
      ['placeholder', 'title', 'aria-label'].forEach((a) => {
        if (!el.hasAttribute(a) || el.closest('[data-i18n-ph]')) { return; }
        const k = '__o_' + a; if (el[k] === undefined) { el[k] = el.getAttribute(a); }
        const tr = l === 'en' ? null : lookup(l, el[k]);
        el.setAttribute(a, tr || el[k]);
      });
    });
  }
  let obs = null, queued = false;
  const OPTS = { childList: true, subtree: true, characterData: true };
  function translateAll() {
    if (!IDX) { build(); }
    if (obs) { obs.disconnect(); }
    walk(document.body);
    if (!obs) {
      obs = new MutationObserver(() => {
        if (queued) { return; } queued = true;
        requestAnimationFrame(() => { queued = false; obs.disconnect(); walk(document.body); obs.observe(document.body, OPTS); });
      });
    }
    obs.observe(document.body, OPTS);
  }
  /* ---- Currency (display only; payments are charged in IDR) ---- */
  const curCode = () => { const c = load('ct_cur'); return (window.CT.currencies && window.CT.currencies[c]) ? c : 'IDR'; };
  function money(idr) {
    const code = curCode(), cfg = (window.CT.currencies || {})[code] || { rate: 1, locale: 'id-ID' };
    try { return new Intl.NumberFormat(cfg.locale, { style: 'currency', currency: code, maximumFractionDigits: (code === 'IDR' || code === 'JPY') ? 0 : 2 }).format(idr * cfg.rate); }
    catch (e) { return 'Rp ' + Math.round(idr).toLocaleString('id-ID'); }
  }
  function refreshMoney() {
    document.querySelectorAll('[data-idr]').forEach((el) => { el.textContent = money(Number(el.dataset.idr)); });
    const cs = document.getElementById('cur-sel');
    if (cs) { if (!cs.options.length) { Object.keys(window.CT.currencies || {}).forEach((c) => cs.add(new Option(c, c))); } cs.value = curCode(); }
  }

  function apply() {
    const l = get();
    document.documentElement.lang = l; document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-i18n]').forEach((el) => { const v = tr(l, el.dataset.i18n); if (v) el.textContent = v; });
    document.querySelectorAll('[data-i18n-ph]').forEach((el) => { el.placeholder = tr(l, el.dataset.i18nPh); });
    document.querySelectorAll('[data-lang-select]').forEach((el) => { if (!el.options.length) { SUPPORTED.forEach((c) => el.add(new Option(NAMES[c], c))); } el.value = l; });
    fillCountries();
    translateAll();
    refreshMoney();
    document.dispatchEvent(new CustomEvent('ct:lang', { detail: l }));
  }
  window.CT = window.CT || {};
  CT.t = (k) => tr(get(), k);
  CT.tx = (x) => { if (!IDX) { build(); } return lookup(get(), String(x)) || x; };
  CT.locale = () => LOCALE[get()] || 'en-GB';
  /* Waktu & tanggal sesuai negara terpilih */
  CT.tz = () => { const c = load(CKEY) || ''; return c && TZ[c] ? validTz(TZ[c]) : undefined; };
  CT.timeStr = (d) => d.toLocaleTimeString(CT.locale(), { hourCycle: 'h23', timeZone: CT.tz() });
  CT.dateStr = (d, o) => d.toLocaleDateString(CT.locale(), Object.assign({ timeZone: CT.tz() }, o));
  CT.tzLabel = () => {
    const z = CT.tz() || Intl.DateTimeFormat().resolvedOptions().timeZone;
    let off = ''; try { off = new Intl.DateTimeFormat('en', { timeZone: z, timeZoneName: 'shortOffset' }).formatToParts(new Date()).find((p) => p.type === 'timeZoneName').value; } catch (e) {}
    return z.replace(/_/g, ' ') + (off ? ' (' + off.replace('GMT', 'UTC') + ')' : '');
  };
  CT.money = money; CT.refreshMoney = refreshMoney;
  CT.setCurrency = (c) => { store('ct_cur', c); refreshMoney(); };
  CT.lang = get;
  CT.country = () => load(CKEY) || '';
  CT.setLang = (l) => { store(KEY, l); apply(); };
  /* Choosing a country switches the language (e.g. China -> 简体中文) and tells the dashboard to filter the map. */
  CT.setCountry = (c) => { store(CKEY, c); if (c) { store(KEY, CC2LANG[c] || 'en'); } apply(); document.dispatchEvent(new CustomEvent('ct:country', { detail: c })); };
  CT.inCountry = (p) => !CT.country() || p.origin_country === CT.country() || p.dest_country === CT.country();
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lang-select]').forEach((el) => el.addEventListener('change', () => CT.setLang(el.value)));
    const cu = document.getElementById('cur-sel'); if (cu) { cu.addEventListener('change', () => CT.setCurrency(cu.value)); }
    const cs = document.getElementById('country-sel'); if (cs) { cs.addEventListener('change', () => CT.setCountry(cs.value)); }
    apply();
  });
})();
