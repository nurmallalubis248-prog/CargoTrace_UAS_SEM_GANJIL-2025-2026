<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Adds the "Hubungi Admin" button to guest pages (login/register).
 * The button is placed below the CargoTrace footer description.
 */
class InjectAdminContact
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (
            ! $request->isMethod('GET')
            || ! in_array($request->path(), ['login', 'register'], true)
            || ! str_contains(
                (string) $response->headers->get('Content-Type'),
                'text/html'
            )
        ) {
            return $response;
        }

        $html = $response->getContent();

        if (
            ! is_string($html)
            || ! str_contains($html, '</body>')
            || str_contains($html, 'id="ct-contact"')
        ) {
            return $response;
        }

        $block = $this->block();

        if ($block !== '') {
            $response->setContent(
                str_replace('</body>', $block . '</body>', $html)
            );
        }

        return $response;
    }

    private function block(): string
    {
        $c = config('contact');

        $e = fn ($v) => e((string) $v);

        $rows = '';

        $wa = $c['whatsapp']
            ? 'https://wa.me/' . $c['whatsapp']
                . '?text=' . rawurlencode($c['whatsapp_text'])
            : null;

        // WhatsApp
        if ($wa) {
            $rows .= '<a href="' . $e($wa) . '" target="_blank"
                rel="noopener noreferrer">
                <b>WhatsApp</b>
                <span>+' . $e($c['whatsapp']) . '</span>
            </a>';
        }

        // Phone
        if ($c['phone']) {
            $rows .= '<a href="tel:'
                . $e(preg_replace('/[^\d+]/', '', $c['phone']))
                . '">
                <b>Telepon</b>
                <span>' . $e($c['phone']) . '</span>
            </a>';
        }

        // Instagram
        if ($c['instagram']) {
            $rows .= '<a href="https://instagram.com/'
                . $e($c['instagram'])
                . '" target="_blank"
                rel="noopener noreferrer">
                <b>Instagram</b>
                <span>@' . $e($c['instagram']) . '</span>
            </a>';
        }

        // Email
        if ($c['email']) {
            $rows .= '<a href="mailto:' . $e($c['email']) . '">
                <b>Email</b>
                <span>' . $e($c['email']) . '</span>
            </a>';
        }

        // Hide button if no contact information is configured.
        if ($rows === '') {
            return '';
        }

        // CSS
        $css = <<<'CSS'
#ct-contact {
    position: relative;
    display: block;
    width: fit-content;
    max-width: 100%;
    margin-top: 18px;
    font-family: system-ui, sans-serif;
    z-index: 20;
}

#ct-contact .ct-c-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    border: none;
    cursor: pointer;
    border-radius: 999px;

    padding: 12px 24px;

    background: #25d366;
    color: #ffffff;

    font-weight: 700;
    font-size: 15px;

    box-shadow: 0 5px 15px rgba(37, 211, 102, 0.20);

    transition:
        background 0.2s ease,
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

#ct-contact .ct-c-btn:hover {
    background: #20bd5a;
    transform: translateY(-1px);
    box-shadow: 0 7px 20px rgba(37, 211, 102, 0.30);
}

#ct-contact .ct-c-btn:active {
    transform: scale(0.97);
}

#ct-contact .ct-c-card {
    display: none;

    position: absolute;
    left: 0;
    bottom: calc(100% + 12px);

    width: 280px;
    max-width: calc(100vw - 32px);

    background: #ffffff;
    color: #2b3027;

    border-radius: 16px;
    padding: 12px;

    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.20);

    z-index: 99999;
}

#ct-contact.open .ct-c-card {
    display: block;
}

#ct-contact h4 {
    margin: 4px 8px 10px;
    font-size: 15px;
    font-weight: 700;
}

#ct-contact .ct-c-card a {
    display: flex;
    flex-direction: column;

    padding: 10px 12px;

    border-radius: 10px;

    text-decoration: none;
    color: inherit;

    transition: background 0.2s ease;
}

#ct-contact .ct-c-card a:hover {
    background: #f3f4f6;
}

#ct-contact .ct-c-card a b {
    font-size: 12px;
    color: #c2477a;
}

#ct-contact .ct-c-card a span {
    font-size: 14px;
    word-break: break-word;
}

#ct-contact svg {
    flex-shrink: 0;
}

@media (max-width: 600px) {
    #ct-contact {
        margin-top: 14px;
    }

    #ct-contact .ct-c-btn {
        padding: 11px 20px;
        font-size: 14px;
    }

    #ct-contact .ct-c-card {
        width: 260px;
    }
}
CSS;

        // WhatsApp icon
        $icon = <<<'SVG'
<svg
    viewBox="0 0 24 24"
    width="20"
    height="20"
    fill="currentColor"
    aria-hidden="true"
>
    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.8 14.2c-.2.7-1.4 1.3-1.9 1.3-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5-4.5-.2-.2-1.2-1.6-1.2-3s.8-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1 .9 1.9 1.2 2.2 1.3.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2-.1.8-.1 1.5Z"/>
</svg>
SVG;

        return '
<style>
' . $css . '
</style>

<div id="ct-contact" data-no-i18n>

    <div class="ct-c-card">
        <h4>Hubungi Admin</h4>
        ' . $rows . '
    </div>

    <button
        type="button"
        class="ct-c-btn"
        aria-expanded="false"
        aria-label="Hubungi Admin"
        onclick="
            var p = this.parentNode;
            p.classList.toggle(\'open\');
            this.setAttribute(
                \'aria-expanded\',
                p.classList.contains(\'open\')
            );
        "
    >
        ' . $icon . '
        Hubungi Admin
    </button>

</div>

<script>
(function () {

    function placeContactButton() {

        var contact = document.getElementById("ct-contact");

        if (!contact) {
            return;
        }

        /*
         * Cari deskripsi CargoTrace.
         */
        var elements = document.querySelectorAll(
            "p, div, span"
        );

        var description = null;

        for (var i = 0; i < elements.length; i++) {

            var text = elements[i].textContent.trim();

            if (
                text.includes(
                    "Visibilitas dan pelacakan pengiriman global"
                )
                &&
                elements[i].children.length === 0
            ) {
                description = elements[i];
                break;
            }
        }

        /*
         * Jika deskripsi ditemukan,
         * letakkan tombol tepat di bawahnya.
         */
        if (description) {

            description.insertAdjacentElement(
                "afterend",
                contact
            );

            return;
        }

        /*
         * Jika deskripsi tidak ditemukan,
         * cari footer sebagai fallback.
         */
        var footer = document.querySelector(
            "footer, .footer, [class*=footer]"
        );

        if (footer) {
            footer.appendChild(contact);
        }
    }

    /*
     * Pastikan DOM sudah siap.
     */
    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            placeContactButton
        );

    } else {

        placeContactButton();

    }

})();
</script>
';
    }
}