@props([
    'label',
    'name',
    'value' => null,
    'required' => false,
])

@php
    $rawValue = old($name);

    if ($rawValue === null) {
        $rawValue = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : ($value ?: '');
    }
@endphp

<div
    class="relative"
    x-data="jaheshPersianDatePicker(@js($rawValue))"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @date-picker-clear.window="if (!$event.detail?.name || $event.detail.name === @js($name)) clear()"
>
    <label for="{{ $name }}_display" class="form-label">
        {{ $label }}
        @if($required)<span class="text-red-600">*</span>@endif
    </label>

    <input type="hidden" name="{{ $name }}" :value="gregorian">

    <div class="relative">
        <input
            id="{{ $name }}_display"
            type="text"
            x-model="display"
            @focus="open = true"
            @click="open = true"
            @input.debounce.250ms="parseTyped()"
            inputmode="numeric"
            autocomplete="off"
            placeholder="۱۴۰۵/۰۵/۲۶"
            class="form-control pl-11"
            dir="ltr"
            @if($required) required @endif
        >

        <button
            type="button"
            class="absolute left-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-md text-gray-500 hover:bg-gray-100"
            @click="open = !open"
            aria-label="انتخاب تاریخ"
            tabindex="-1"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M7 3v3M17 3v3M4.5 9h15M5 5.5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20.5H5A1.5 1.5 0 0 1 3.5 19V7A1.5 1.5 0 0 1 5 5.5Z"/>
            </svg>
        </button>
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-[292px] max-w-[calc(100vw-2rem)] rounded-xl border border-gray-200 bg-white p-3 shadow-lg"
        dir="rtl"
    >
        <div class="mb-3 flex items-center justify-between">
            <button type="button" class="grid h-9 w-9 place-items-center rounded-lg hover:bg-gray-100" @click="nextMonth()" aria-label="ماه بعد">‹</button>
            <strong class="text-sm" x-text="monthTitle"></strong>
            <button type="button" class="grid h-9 w-9 place-items-center rounded-lg hover:bg-gray-100" @click="previousMonth()" aria-label="ماه قبل">›</button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-gray-400">
            <template x-for="dayName in weekDays" :key="dayName">
                <span class="py-1" x-text="dayName"></span>
            </template>
        </div>

        <div class="mt-1 grid grid-cols-7 gap-1">
            <template x-for="blank in firstDayOffset" :key="'blank-'+blank">
                <span class="h-9"></span>
            </template>

            <template x-for="day in daysInViewMonth" :key="day">
                <button
                    type="button"
                    class="grid h-9 place-items-center rounded-lg text-sm transition hover:bg-emerald-50 hover:text-emerald-800"
                    :class="{
                        'bg-emerald-500 font-bold text-emerald-950 hover:bg-emerald-500 hover:text-emerald-950': isSelected(day),
                        'ring-1 ring-emerald-300': isToday(day) && !isSelected(day)
                    }"
                    @click="selectDay(day)"
                    x-text="toPersianDigits(day)"
                ></button>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-xs">
            <button type="button" class="font-semibold text-emerald-700 hover:text-emerald-800" @click="selectToday()">امروز</button>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="clear()">پاک کردن</button>
        </div>
    </div>

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

@once
<script>
    window.jaheshPersianDatePicker = function (initialGregorian = '') {
        const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        const div = (a, b) => ~~(a / b);
        const mod = (a, b) => a - ~~(a / b) * b;

        function jalCal(jy, withoutLeap = false) {
            const bl = breaks.length;
            const gy = jy + 621;
            let leapJ = -14;
            let jp = breaks[0];
            let jm;
            let jump = 0;
            let leap;
            let leapG;
            let march;
            let n;
            let i;

            if (jy < jp || jy >= breaks[bl - 1]) {
                throw new Error('Invalid Jalaali year');
            }

            for (i = 1; i < bl; i += 1) {
                jm = breaks[i];
                jump = jm - jp;

                if (jy < jm) {
                    break;
                }

                leapJ += div(jump, 33) * 8 + div(mod(jump, 33), 4);
                jp = jm;
            }

            n = jy - jp;
            leapJ += div(n, 33) * 8 + div(mod(n, 33) + 3, 4);

            if (mod(jump, 33) === 4 && jump - n === 4) {
                leapJ += 1;
            }

            leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
            march = 20 + leapJ - leapG;

            if (withoutLeap) {
                return { gy, march };
            }

            if (jump - n < 6) {
                n = n - jump + div(jump + 4, 33) * 33;
            }

            leap = mod(mod(n + 1, 33) - 1, 4);

            if (leap === -1) {
                leap = 4;
            }

            return { leap, gy, march };
        }

        function g2d(gy, gm, gd) {
            let d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4)
                + div(153 * mod(gm + 9, 12) + 2, 5)
                + gd - 34840408;

            d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;

            return d;
        }

        function d2g(jdn) {
            let j = 4 * jdn + 139361631;
            j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
            const i = div(mod(j, 1461), 4) * 5 + 308;
            const gd = div(mod(i, 153), 5) + 1;
            const gm = mod(div(i, 153), 12) + 1;
            const gy = div(j, 1461) - 100100 + div(8 - gm, 6);

            return { gy, gm, gd };
        }

        function j2d(jy, jm, jd) {
            const r = jalCal(jy, true);

            return g2d(r.gy, 3, r.march)
                + (jm - 1) * 31
                - div(jm, 7) * (jm - 7)
                + jd - 1;
        }

        function d2j(jdn) {
            const g = d2g(jdn);
            let jy = g.gy - 621;
            const r = jalCal(jy, false);
            const jdn1f = g2d(g.gy, 3, r.march);
            let k = jdn - jdn1f;
            let jd;
            let jm;

            if (k >= 0) {
                if (k <= 185) {
                    jm = 1 + div(k, 31);
                    jd = mod(k, 31) + 1;

                    return { jy, jm, jd };
                }

                k -= 186;
            } else {
                jy -= 1;
                k += 179;

                if (r.leap === 1) {
                    k += 1;
                }
            }

            jm = 7 + div(k, 30);
            jd = mod(k, 30) + 1;

            return { jy, jm, jd };
        }

        function toJalaali(gy, gm, gd) {
            return d2j(g2d(gy, gm, gd));
        }

        function toGregorian(jy, jm, jd) {
            return d2g(j2d(jy, jm, jd));
        }

        function isLeapJalaaliYear(jy) {
            return jalCal(jy).leap === 0;
        }

        function jalaaliMonthLength(jy, jm) {
            if (jm <= 6) return 31;
            if (jm <= 11) return 30;

            return isLeapJalaaliYear(jy) ? 30 : 29;
        }

        function isValidJalaaliDate(jy, jm, jd) {
            return jy >= -61
                && jy <= 3177
                && jm >= 1
                && jm <= 12
                && jd >= 1
                && jd <= jalaaliMonthLength(jy, jm);
        }

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function normalizeDigits(value) {
            return String(value ?? '')
                .replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
                .replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
        }

        function persianDigits(value) {
            return String(value).replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        }

        function gregorianString(gy, gm, gd) {
            return `${gy}-${pad(gm)}-${pad(gd)}`;
        }

        function parseGregorian(value) {
            const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);

            if (!match) return null;

            return {
                gy: Number(match[1]),
                gm: Number(match[2]),
                gd: Number(match[3]),
            };
        }

        const now = new Date();
        const todayJalali = toJalaali(now.getFullYear(), now.getMonth() + 1, now.getDate());
        const initial = parseGregorian(initialGregorian);
        const initialJalali = initial ? toJalaali(initial.gy, initial.gm, initial.gd) : todayJalali;

        return {
            open: false,
            gregorian: initialGregorian || '',
            display: initial
                ? persianDigits(`${initialJalali.jy}/${pad(initialJalali.jm)}/${pad(initialJalali.jd)}`)
                : '',
            viewYear: initialJalali.jy,
            viewMonth: initialJalali.jm,
            selectedYear: initial ? initialJalali.jy : null,
            selectedMonth: initial ? initialJalali.jm : null,
            selectedDay: initial ? initialJalali.jd : null,
            weekDays: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],

            get monthTitle() {
                return `${monthNames[this.viewMonth - 1]} ${persianDigits(this.viewYear)}`;
            },

            get daysInViewMonth() {
                return jalaaliMonthLength(this.viewYear, this.viewMonth);
            },

            get firstDayOffset() {
                const g = toGregorian(this.viewYear, this.viewMonth, 1);
                const weekDay = new Date(g.gy, g.gm - 1, g.gd).getDay();

                return (weekDay + 1) % 7;
            },

            toPersianDigits(value) {
                return persianDigits(value);
            },

            parseTyped() {
                const normalized = normalizeDigits(this.display)
                    .trim()
                    .replace(/[.\-_]/g, '/')
                    .replace(/\/+/g, '/');

                if (normalized === '') {
                    this.gregorian = '';
                    this.selectedYear = null;
                    this.selectedMonth = null;
                    this.selectedDay = null;
                    return;
                }

                const match = normalized.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);

                if (!match) {
                    this.gregorian = '';
                    return;
                }

                const jy = Number(match[1]);
                const jm = Number(match[2]);
                const jd = Number(match[3]);

                if (!isValidJalaaliDate(jy, jm, jd)) {
                    this.gregorian = '';
                    return;
                }

                const g = toGregorian(jy, jm, jd);

                this.gregorian = gregorianString(g.gy, g.gm, g.gd);
                this.selectedYear = jy;
                this.selectedMonth = jm;
                this.selectedDay = jd;
                this.viewYear = jy;
                this.viewMonth = jm;
                this.display = persianDigits(`${jy}/${pad(jm)}/${pad(jd)}`);
            },

            selectDay(day) {
                const g = toGregorian(this.viewYear, this.viewMonth, day);

                this.gregorian = gregorianString(g.gy, g.gm, g.gd);
                this.selectedYear = this.viewYear;
                this.selectedMonth = this.viewMonth;
                this.selectedDay = day;
                this.display = persianDigits(`${this.viewYear}/${pad(this.viewMonth)}/${pad(day)}`);
                this.open = false;
            },

            isSelected(day) {
                return this.selectedYear === this.viewYear
                    && this.selectedMonth === this.viewMonth
                    && this.selectedDay === day;
            },

            isToday(day) {
                return todayJalali.jy === this.viewYear
                    && todayJalali.jm === this.viewMonth
                    && todayJalali.jd === day;
            },

            selectToday() {
                this.viewYear = todayJalali.jy;
                this.viewMonth = todayJalali.jm;
                this.selectDay(todayJalali.jd);
            },

            previousMonth() {
                if (this.viewMonth === 1) {
                    this.viewMonth = 12;
                    this.viewYear -= 1;
                    return;
                }

                this.viewMonth -= 1;
            },

            nextMonth() {
                if (this.viewMonth === 12) {
                    this.viewMonth = 1;
                    this.viewYear += 1;
                    return;
                }

                this.viewMonth += 1;
            },

            clear() {
                this.gregorian = '';
                this.display = '';
                this.selectedYear = null;
                this.selectedMonth = null;
                this.selectedDay = null;
                this.open = false;
            },
        };
    };
</script>
@endonce
