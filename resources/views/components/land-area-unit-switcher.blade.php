{{--
  Reusable land display-unit switcher (Acre / Kanal / Marla / Sqft).

  Usage:
    <x-land-area-unit-switcher scope="#report-land-ledger" />
    <x-land-area-unit-switcher scope=".my-card" :value="'kanal'" size="sm" />

  Pair with labels that carry canonical marla:
    <span data-land-marla="1340.5">…</span>
    <span data-land-marla="12" data-land-empty="—">…</span>
--}}
@props([
    'scope' => null,
    'value' => null,
    'size' => 'sm',
    'label' => 'Show as',
    'storageKey' => 'society_land_display_unit',
])

@php
    $units = \App\Support\LandMeasure::displayUnits();
    $selected = \App\Support\LandMeasure::normalizeDisplayUnit(
        $value ?? request('land_unit') ?? config('land_measure.default_display_unit', 'acre')
    );
    $config = \App\Support\LandMeasure::conversionConfig();
    $btnClass = $size === 'sm' ? 'btn btn-sm' : 'btn';
@endphp

<div
    {{ $attributes->class(['land-area-unit-switcher', 'd-inline-flex', 'flex-wrap', 'align-items-center', 'gap-2']) }}
    data-land-unit-switcher
    data-land-scope="{{ $scope }}"
    data-land-storage-key="{{ $storageKey }}"
    data-land-config='@json($config)'
>
    @if($label)
        <span class="small text-muted text-nowrap">{{ $label }}</span>
    @endif
    <div class="btn-group" role="group" aria-label="Land area display unit">
        @foreach($units as $key => $unitLabel)
            <button
                type="button"
                class="{{ $btnClass }} btn-outline-theme land-area-unit-btn {{ $selected === $key ? 'active' : '' }}"
                data-land-unit="{{ $key }}"
                aria-pressed="{{ $selected === $key ? 'true' : 'false' }}"
            >{{ $unitLabel }}</button>
        @endforeach
    </div>
</div>

@once
    @push('head')
        <style>
            .land-area-unit-switcher .land-area-unit-btn.active {
                background: var(--accent-orange, #f97316) !important;
                border-color: var(--accent-orange, #f97316) !important;
                color: #fff !important;
            }
            [data-land-marla] {
                white-space: nowrap;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
        (function () {
            if (window.LandAreaConverter) return;

            function num(v, fallback) {
                var n = Number(v);
                return Number.isFinite(n) ? n : fallback;
            }

            window.LandAreaConverter = {
                config: {
                    kanal_per_acre: 8,
                    marla_per_kanal: 20,
                    sqft_per_marla: 225,
                    marla_per_acre: 160,
                    default_display_unit: 'acre'
                },

                setConfig: function (cfg) {
                    if (!cfg) return;
                    this.config.kanal_per_acre = num(cfg.kanal_per_acre, 8);
                    this.config.marla_per_kanal = num(cfg.marla_per_kanal, 20);
                    this.config.sqft_per_marla = num(cfg.sqft_per_marla, 225);
                    this.config.marla_per_acre = this.config.kanal_per_acre * this.config.marla_per_kanal;
                    if (cfg.default_display_unit) {
                        this.config.default_display_unit = String(cfg.default_display_unit);
                    }
                },

                normalizeUnit: function (unit) {
                    unit = String(unit || '').toLowerCase();
                    if (unit === 'akms' || !unit) return 'acre';
                    if (unit === 'acre' || unit === 'kanal' || unit === 'marla' || unit === 'sqft') return unit;
                    return this.config.default_display_unit || 'acre';
                },

                toMarla: function (amount, unit) {
                    amount = num(amount, 0);
                    unit = this.normalizeUnit(unit);
                    if (unit === 'marla') return amount;
                    if (unit === 'kanal') return amount * this.config.marla_per_kanal;
                    if (unit === 'acre') return amount * this.config.marla_per_acre;
                    if (unit === 'sqft') return amount / this.config.sqft_per_marla;
                    return 0;
                },

                fromMarla: function (marla, unit) {
                    marla = num(marla, 0);
                    unit = this.normalizeUnit(unit);
                    if (unit === 'marla') return marla;
                    if (unit === 'kanal') return marla / this.config.marla_per_kanal;
                    if (unit === 'acre') return marla / this.config.marla_per_acre;
                    if (unit === 'sqft') return marla * this.config.sqft_per_marla;
                    return marla;
                },

                convert: function (amount, fromUnit, toUnit) {
                    return this.fromMarla(this.toMarla(amount, fromUnit), toUnit);
                },

                partsFromMarla: function (totalMarla, displayUnit) {
                    displayUnit = this.normalizeUnit(displayUnit);
                    var eps = 1e-9;
                    totalMarla = num(totalMarla, 0);
                    if (Math.abs(totalMarla) <= eps) {
                        return { acre: 0, kanal: 0, marla: 0, sqft: 0 };
                    }
                    var sign = totalMarla < 0 ? -1 : 1;
                    var abs = Math.abs(totalMarla);
                    var marlaPerKanal = this.config.marla_per_kanal;
                    var marlaPerAcre = this.config.marla_per_acre;
                    var sqftPerMarla = this.config.sqft_per_marla;
                    var wholeMarla = Math.floor(abs + eps);
                    var frac = Math.max(0, abs - wholeMarla);
                    var sqft = frac > eps ? Math.round(frac * sqftPerMarla) : 0;

                    if (displayUnit === 'sqft') {
                        return { acre: 0, kanal: 0, marla: 0, sqft: sign * Math.round(abs * sqftPerMarla) };
                    }
                    if (displayUnit === 'marla') {
                        return { acre: 0, kanal: 0, marla: sign * wholeMarla, sqft: sign * sqft };
                    }
                    if (displayUnit === 'kanal') {
                        var k = Math.floor(wholeMarla / marlaPerKanal);
                        var m = wholeMarla - (k * marlaPerKanal);
                        return { acre: 0, kanal: sign * k, marla: sign * m, sqft: sign * sqft };
                    }
                    var a = Math.floor(wholeMarla / marlaPerAcre);
                    var r = wholeMarla - (a * marlaPerAcre);
                    var kk = Math.floor(r / marlaPerKanal);
                    var mm = r - (kk * marlaPerKanal);
                    return { acre: sign * a, kanal: sign * kk, marla: sign * mm, sqft: sign * sqft };
                },

                formatLabelFromMarla: function (totalMarla, displayUnit) {
                    displayUnit = this.normalizeUnit(displayUnit);
                    totalMarla = num(totalMarla, 0);
                    var p = this.partsFromMarla(totalMarla, displayUnit);
                    var neg = totalMarla < -1e-9;
                    var prefix = neg ? '−' : '';
                    if (displayUnit === 'sqft') return prefix + 'SQFT ' + Math.abs(p.sqft);
                    if (displayUnit === 'marla') return prefix + 'M ' + Math.abs(p.marla) + ' — SQFT ' + Math.abs(p.sqft);
                    if (displayUnit === 'kanal') {
                        return prefix + 'K ' + Math.abs(p.kanal) + ' — M ' + Math.abs(p.marla) + ' — SQFT ' + Math.abs(p.sqft);
                    }
                    return prefix + 'A ' + Math.abs(p.acre) + ' — K ' + Math.abs(p.kanal) + ' — M ' + Math.abs(p.marla) + ' — SQFT ' + Math.abs(p.sqft);
                },

                apply: function (root, unit) {
                    unit = this.normalizeUnit(unit);
                    var scope = root || document;
                    scope.querySelectorAll('[data-land-marla]').forEach(function (el) {
                        var raw = el.getAttribute('data-land-marla');
                        if (raw === null || raw === '') {
                            el.textContent = el.getAttribute('data-land-empty') || '—';
                            return;
                        }
                        var marla = Number(raw);
                        if (!Number.isFinite(marla) || Math.abs(marla) < 1e-12) {
                            el.textContent = el.getAttribute('data-land-empty') || '—';
                            return;
                        }
                        el.textContent = window.LandAreaConverter.formatLabelFromMarla(marla, unit);
                    });
                    document.dispatchEvent(new CustomEvent('land-area-unit-changed', { detail: { unit: unit } }));
                }
            };

            function bindSwitcher(switcher) {
                if (!switcher || switcher._landBound) return;
                switcher._landBound = true;
                var cfgAttr = switcher.getAttribute('data-land-config');
                try {
                    if (cfgAttr) window.LandAreaConverter.setConfig(JSON.parse(cfgAttr));
                } catch (e) {}

                var storageKey = switcher.getAttribute('data-land-storage-key') || 'society_land_display_unit';
                var scopeSel = switcher.getAttribute('data-land-scope');
                var saved = null;
                try { saved = localStorage.getItem(storageKey); } catch (e) {}
                var current = window.LandAreaConverter.normalizeUnit(saved || window.LandAreaConverter.config.default_display_unit);

                function scopeRoot() {
                    if (!scopeSel) return document;
                    return document.querySelector(scopeSel) || document;
                }

                function setActive(unit) {
                    current = window.LandAreaConverter.normalizeUnit(unit);
                    switcher.querySelectorAll('.land-area-unit-btn').forEach(function (btn) {
                        var on = btn.getAttribute('data-land-unit') === current;
                        btn.classList.toggle('active', on);
                        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    });
                    try { localStorage.setItem(storageKey, current); } catch (e) {}
                    window.LandAreaConverter.apply(scopeRoot(), current);
                }

                switcher.querySelectorAll('.land-area-unit-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setActive(btn.getAttribute('data-land-unit'));
                    });
                });

                setActive(current);
            }

            function boot() {
                document.querySelectorAll('[data-land-unit-switcher]').forEach(bindSwitcher);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }

            window.LandAreaConverter.bindAll = boot;
        })();
        </script>
    @endpush
@endonce
