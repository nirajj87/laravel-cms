<?php

namespace App\Support;

class SiteTheme
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function theme(array $input = []): array
    {
        $defaults = config('site.theme');
        $hex = function (string $key) use ($input, $defaults): string {
            $value = (string) ($input[$key] ?? $defaults[$key]);

            return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? strtolower($value) : $defaults[$key];
        };

        $font = (string) ($input['font'] ?? $defaults['font']);
        $radius = (string) ($input['radius'] ?? $defaults['radius']);
        $container = (string) ($input['container'] ?? $defaults['container']);
        $columns = (int) ($input['columns'] ?? $defaults['columns']);
        $perPage = (int) ($input['per_page'] ?? $defaults['per_page']);

        return [
            'primary' => $hex('primary'),
            'secondary' => $hex('secondary'),
            'background' => $hex('background'),
            'text' => $hex('text'),
            'button' => $hex('button'),
            'font' => array_key_exists($font, config('site.fonts')) ? $font : $defaults['font'],
            'radius' => in_array($radius, config('site.radii'), true) ? $radius : $defaults['radius'],
            'card_style' => array_key_exists((string) ($input['card_style'] ?? ''), config('site.card_styles')) ? $input['card_style'] : $defaults['card_style'],
            'header_style' => array_key_exists((string) ($input['header_style'] ?? ''), config('site.header_styles')) ? $input['header_style'] : $defaults['header_style'],
            'footer_style' => in_array((string) ($input['footer_style'] ?? ''), ['solid', 'muted'], true) ? $input['footer_style'] : $defaults['footer_style'],
            'container' => in_array($container, config('site.containers'), true) ? $container : $defaults['container'],
            'columns' => in_array($columns, config('site.columns'), true) ? $columns : $defaults['columns'],
            'mode' => array_key_exists((string) ($input['mode'] ?? ''), config('site.modes')) ? $input['mode'] : $defaults['mode'],
            'listing' => array_key_exists((string) ($input['listing'] ?? ''), config('site.listing_modes')) ? $input['listing'] : $defaults['listing'],
            'per_page' => max(4, min(48, $perPage > 0 ? $perPage : $defaults['per_page'])),
            'hover' => self::flag($input, 'hover'),
            'card_fade' => self::flag($input, 'card_fade'),
            'smooth_scroll' => self::flag($input, 'smooth_scroll'),
            'button_press' => self::flag($input, 'button_press'),
            'image_hover' => self::flag($input, 'image_hover'),
            'page_fade' => self::flag($input, 'page_fade'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function site(array $input = []): array
    {
        $defaults = config('site.site');
        $size = (string) ($input['logo_size'] ?? $defaults['logo_size']);
        $social = [];

        foreach ((array) ($input['social'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $platform = (string) ($item['platform'] ?? '');
            $url = SafeHtml::url($item['url'] ?? null);

            if (! in_array($platform, config('site.social_platforms'), true) || ! $url) {
                continue;
            }

            $social[] = ['platform' => $platform, 'url' => $url];
        }

        return [
            'logo_size' => array_key_exists($size, config('site.logo_sizes')) ? $size : $defaults['logo_size'],
            'show_login' => self::flag($input, 'show_login', (bool) $defaults['show_login']),
            'show_search' => self::flag($input, 'show_search', (bool) $defaults['show_search']),
            'cta_label' => mb_substr(trim((string) ($input['cta_label'] ?? '')), 0, 40),
            'cta_url' => SafeHtml::url($input['cta_url'] ?? null) ?? '',
            'cta_new_tab' => self::flag($input, 'cta_new_tab', false),
            'footer_description' => mb_substr(trim(strip_tags((string) ($input['footer_description'] ?? ''))), 0, 500),
            'copyright' => mb_substr(trim(strip_tags((string) ($input['copyright'] ?? ''))), 0, 180),
            'contact_email' => filter_var($input['contact_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
            'contact_phone' => mb_substr(trim(strip_tags((string) ($input['contact_phone'] ?? ''))), 0, 40),
            'contact_address' => mb_substr(trim(strip_tags((string) ($input['contact_address'] ?? ''))), 0, 240),
            'newsletter' => self::flag($input, 'newsletter', (bool) $defaults['newsletter']),
            'newsletter_heading' => mb_substr(trim(strip_tags((string) ($input['newsletter_heading'] ?? $defaults['newsletter_heading']))), 0, 80),
            'social' => $social,
            'custom_header' => SafeHtml::clean($input['custom_header'] ?? ''),
            'custom_footer' => SafeHtml::clean($input['custom_footer'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public static function css(array $theme, array $site = []): string
    {
        $theme = self::theme($theme);
        $site = self::site($site);
        $font = match ($theme['font']) {
            'source-serif' => '"Source Serif 4", Georgia, serif',
            'system' => 'ui-sans-serif, system-ui, sans-serif',
            default => '"Instrument Sans", ui-sans-serif, system-ui, sans-serif',
        };
        $logo = (int) config('site.logo_sizes.'.$site['logo_size']);
        $darkBg = '#0f172a';
        $darkText = '#e2e8f0';
        $bg = $theme['mode'] === 'dark' ? $darkBg : $theme['background'];
        $text = $theme['mode'] === 'dark' ? $darkText : $theme['text'];
        $header = $theme['header_style'] === 'muted' ? $theme['secondary'] : ($theme['mode'] === 'dark' ? '#111827' : '#ffffff');
        $footer = $theme['footer_style'] === 'muted' ? $theme['secondary'] : ($theme['mode'] === 'dark' ? '#111827' : '#ffffff');
        $cardBorder = $theme['card_style'] === 'flat' ? 'transparent' : 'rgba(15, 23, 42, 0.08)';
        $cardShadow = $theme['card_style'] === 'raised' ? '0 10px 30px rgba(15, 23, 42, 0.06)' : 'none';
        $container = $theme['container'] === 'full' ? 'none' : $theme['container'].'px';

        $css = ':root{--site-primary:'.$theme['primary'].';--site-secondary:'.$theme['secondary'].';--site-bg:'.$bg.';--site-text:'.$text.';--site-button:'.$theme['button'].';--site-radius:'.$theme['radius'].'px;--site-container:'.$container.';--site-gutter:clamp(1rem,2.4vw,2.75rem);--site-columns:'.$theme['columns'].';--site-font:'.$font.';--site-logo:'.$logo.'px;--site-header:'.$header.';--site-footer:'.$footer.';--site-card-border:'.$cardBorder.';--site-card-shadow:'.$cardShadow.';}';

        if ($theme['mode'] === 'system') {
            $css .= '@media (prefers-color-scheme: dark){:root{--site-bg:'.$darkBg.';--site-text:'.$darkText.';--site-header:#111827;--site-footer:#111827;}}';
        }

        $css .= 'body.site-body{background:var(--site-bg);color:var(--site-text);font-family:var(--site-font);}';
        $css .= '.site-wrap{box-sizing:border-box;width:100%;max-width:var(--site-container);margin-inline:auto;padding-inline:var(--site-gutter);}';
        $css .= '.site-header{position:sticky;top:0;z-index:20;background:color-mix(in srgb, var(--site-header) 90%, transparent);backdrop-filter:blur(14px);} .site-header,.site-footer{border-color:var(--site-card-border);} .site-footer{background:var(--site-footer);}';
        $css .= '.site-bar{display:flex;align-items:center;gap:1.25rem;min-height:4.75rem;}';
        $css .= '.site-logo{font-weight:700;color:var(--site-text);text-decoration:none;font-size:1.05rem;} .site-logo img{height:var(--site-logo);width:auto;}';
        $css .= '.site-nav{display:flex;flex-wrap:wrap;gap:.9rem 1.2rem;margin-left:auto;align-items:center;} .site-nav a{color:var(--site-text);text-decoration:none;font-size:.95rem;opacity:.88;} .site-nav a:hover{opacity:1;}';
        $css .= '.site-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.15rem;margin-top:1.15rem;}';
        $css .= '.site-grid{display:grid;grid-template-columns:repeat(var(--site-columns),minmax(0,1fr));gap:1.15rem;}';
        $css .= '.site-card{background:#fff;color:#0f172a;border:1px solid var(--site-card-border);border-radius:var(--site-radius);box-shadow:var(--site-card-shadow);overflow:hidden;display:flex;flex-direction:column;}';
        $css .= '.site-card img,.site-placeholder{width:100%;height:13rem;object-fit:cover;background:#f5f5f4;} .site-placeholder{display:grid;place-items:center;background:linear-gradient(145deg, color-mix(in srgb, var(--site-primary) 82%, #fff), color-mix(in srgb, var(--site-secondary) 62%, #020617));color:#fff;font-size:1.85rem;font-weight:700;letter-spacing:.1em;} .site-card .site-card-body{padding:1.1rem 1.15rem 1.25rem;display:flex;flex-direction:column;gap:.5rem;flex:1;}';
        $css .= '.site-card h2{font-size:1.08rem;line-height:1.3;margin:0;} .site-card p{margin:0;color:#475569;font-size:.94rem;} .site-kicker{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--site-primary);} .site-meta{display:flex;flex-wrap:wrap;gap:.6rem;color:#57534e;font-size:.9rem;}';
        $css .= '.site-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:calc(var(--site-radius) - 4px);padding:.6rem 1rem;background:var(--site-button);color:#fff;text-decoration:none;font-weight:600;font-size:.9rem;border:1px solid var(--site-button);margin-top:auto;align-self:flex-start;}';
        $css .= '.site-btn-secondary{background:var(--site-secondary);} .site-btn-outline{background:transparent;color:var(--site-button);}';
        $css .= '.site-input,.site-select{width:100%;border:1px solid rgba(15,23,42,.15);border-radius:calc(var(--site-radius) - 6px);padding:.8rem .95rem;background:#fff;color:#0f172a;}';
        $css .= '.site-main .site-row:first-child{margin-top:0;}';
        $css .= '.site-hero{position:relative;isolation:isolate;overflow:hidden;display:grid;place-items:center;text-align:center;min-height:min(78vh,42rem);width:calc(100% + (var(--site-gutter) * 2));margin-inline:calc(var(--site-gutter) * -1);padding:5rem var(--site-gutter) 3.8rem;background:radial-gradient(1200px 480px at 12% -8%, color-mix(in srgb, var(--site-primary) 58%, transparent), transparent 62%), radial-gradient(900px 380px at 88% 10%, color-mix(in srgb, var(--site-secondary) 40%, transparent), transparent 55%), linear-gradient(165deg, color-mix(in srgb, var(--site-primary) 88%, #020617), #020617 74%);}';
        $css .= '.site-hero-canvas{position:absolute;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;} .site-hero-copy{position:relative;z-index:1;width:min(100%, 56rem);}';
        $css .= '.site-hero .site-kicker{color:rgba(248,250,252,.72);} .site-hero h1{color:#f8fafc;font-size:clamp(2.5rem,5.6vw,4.8rem);line-height:1.02;letter-spacing:-.04em;margin:0;} .site-hero .site-lead{max-width:40rem;margin:1rem auto 0;color:rgba(248,250,252,.86);font-size:1.12rem;}';
        $css .= '.site-types{display:flex;justify-content:center;gap:.4rem .9rem;flex-wrap:wrap;margin-top:1.05rem;} .site-type{color:var(--site-text);text-decoration:none;font-size:.92rem;} .site-type.is-current{font-weight:700;}';
        $css .= '.site-hero a{color:#f8fafc;} .site-hero .site-type{color:rgba(248,250,252,.82);} .site-hero .site-type.is-current{color:#fff;} .site-search{display:grid;grid-template-columns:1fr auto;gap:.65rem;max-width:46rem;margin:1.5rem auto 0;} .site-hero .site-input{border:0;box-shadow:0 18px 40px rgba(2,6,23,.18);} .site-hero .site-btn{margin-top:0;align-self:stretch;}';
        $css .= '#categories{margin-top:.5rem;padding:1rem 1.1rem;background:#fff;border:1px solid var(--site-card-border);border-radius:var(--site-radius);box-shadow:var(--site-card-shadow);}';
        $css .= '.site-footer{border-top:1px solid var(--site-card-border);margin-top:3.5rem;padding:2.4rem 0;} .site-footer a{color:var(--site-text);}';
        $css .= '@media (max-width:1024px){.site-row{grid-template-columns:repeat(2,minmax(0,1fr));}.site-span{grid-column:span var(--span-tablet)!important;}.site-grid{grid-template-columns:repeat(2,minmax(0,1fr));}.site-hero{min-height:28rem;}}';
        $css .= '@media (max-width:700px){.site-bar{flex-wrap:wrap;padding:.8rem 0;}.site-header .site-nav{display:none;width:100%;margin:0;}.site-header .site-nav.is-open{display:flex;}.site-menu-btn{margin-left:auto;} .site-row,.site-grid,.site-search{grid-template-columns:1fr;} .site-span{grid-column:span 1!important;}.site-hero{min-height:24rem;padding-top:3.5rem;}}';

        $motion = '@media (prefers-reduced-motion: no-preference){';

        if ($theme['hover']) {
            $motion .= '.site-card{transition:transform .2s ease;} .site-card:hover{transform:translateY(-3px);}';
        }

        if ($theme['image_hover']) {
            $motion .= '.site-card img{transition:transform .35s ease;} .site-card:hover img{transform:scale(1.04);}';
        }

        if ($theme['button_press']) {
            $motion .= '.site-btn{transition:transform .15s ease, filter .15s ease;} .site-btn:hover{filter:brightness(1.05);} .site-btn:active{transform:translateY(1px);}';
        }

        if ($theme['card_fade'] || $theme['page_fade']) {
            $motion .= '@keyframes site-fade{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}} .site-fade{animation:site-fade .45s ease both;} .site-grid .site-card:nth-child(1){animation-delay:.02s;} .site-grid .site-card:nth-child(2){animation-delay:.06s;} .site-grid .site-card:nth-child(3){animation-delay:.1s;} .site-grid .site-card:nth-child(4){animation-delay:.14s;} .site-grid .site-card:nth-child(5){animation-delay:.18s;} .site-grid .site-card:nth-child(6){animation-delay:.22s;} .site-grid .site-card:nth-child(7){animation-delay:.26s;} .site-grid .site-card:nth-child(8){animation-delay:.3s;}';
        }

        if ($theme['smooth_scroll']) {
            $motion .= 'html{scroll-behavior:smooth;}';
        }

        $motion .= '}@media (prefers-reduced-motion: reduce){html{scroll-behavior:auto;} .site-card,.site-card img,.site-btn{transition:none;animation:none;}}';
        $css .= 'a:focus-visible,button:focus-visible,.site-input:focus-visible,.site-select:focus-visible{outline:2px solid var(--site-primary);outline-offset:2px;}';

        return $css.$motion;
    }

    public static function buttonStyle(?string $style): string
    {
        return array_key_exists((string) $style, config('site.button_styles')) ? (string) $style : 'primary';
    }

    public static function target(mixed $newTab): string
    {
        return filter_var($newTab, FILTER_VALIDATE_BOOL) ? '_blank' : '_self';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function flag(array $input, string $key, bool $default = true): bool
    {
        if (! array_key_exists($key, $input)) {
            return $default;
        }

        return filter_var($input[$key], FILTER_VALIDATE_BOOL);
    }
}
