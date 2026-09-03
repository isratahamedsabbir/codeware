document.addEventListener('alpine:init', () => {
    window.Alpine.data('dateRangePicker', (config) => ({
        open: false,
        fromModel: config.fromModel,
        toModel: config.toModel,
        appliedFrom: config.appliedFrom || '',
        appliedTo: config.appliedTo || '',
        appliedPreset: null,
        workingFrom: null,
        workingTo: null,
        activePreset: null,
        leftMonth: null,
        rightMonth: null,
        panelStyle: '',

        presets: [
            { key: 'all', label: 'All Time' },
            { key: 'today', label: 'Today' },
            { key: 'yesterday', label: 'Yesterday' },
            { key: 'last7', label: 'Last 7 Days' },
            { key: 'last30', label: 'Last 30 Days' },
            { key: 'thisMonth', label: 'This Month' },
            { key: 'lastMonth', label: 'Last Month' },
            { key: 'custom', label: 'Custom Range' },
        ],

        init() {
            this.appliedPreset = this.detectPreset(this.parseISO(this.appliedFrom), this.parseISO(this.appliedTo));
            this.syncFromApplied();

            const handler = (e) => {
                if (!this.$refs.trigger || !document.body.contains(this.$refs.trigger)) {
                    document.removeEventListener('click', handler);
                    return;
                }
                if (!this.open) return;
                if (this.$refs.trigger.contains(e.target)) return;
                if (this.$refs.panel && this.$refs.panel.contains(e.target)) return;
                this.cancel();
            };
            document.addEventListener('click', handler);

            window.addEventListener('resize', () => this.open && this.updatePosition());
            window.addEventListener('scroll', () => this.open && this.updatePosition(), true);
        },

        updatePosition() {
            this.$nextTick(() => {
                const trigger = this.$refs.trigger;
                const panel = this.$refs.panel;
                if (!trigger || !panel) return;

                const rect = trigger.getBoundingClientRect();
                const panelWidth = panel.offsetWidth;
                let left = rect.left;
                const maxLeft = window.innerWidth - panelWidth - 12;
                if (left > maxLeft) left = Math.max(12, maxLeft);

                this.panelStyle = `top:${rect.bottom + 8}px; left:${left}px;`;
            });
        },

        pad(n) {
            return String(n).padStart(2, '0');
        },

        toISO(date) {
            return `${date.getFullYear()}-${this.pad(date.getMonth() + 1)}-${this.pad(date.getDate())}`;
        },

        parseISO(str) {
            if (!str) return null;
            const [y, m, d] = str.split('-').map(Number);
            return new Date(y, m - 1, d);
        },

        startOfMonth(date) {
            return new Date(date.getFullYear(), date.getMonth(), 1);
        },

        addMonths(date, n) {
            return new Date(date.getFullYear(), date.getMonth() + n, 1);
        },

        addDays(date, n) {
            const d = new Date(date);
            d.setDate(d.getDate() + n);
            return d;
        },

        sameDay(a, b) {
            return a && b && this.toISO(a) === this.toISO(b);
        },

        presetRange(key) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            switch (key) {
                case 'all':
                    return [null, null];
                case 'today':
                    return [today, today];
                case 'yesterday': {
                    const y = this.addDays(today, -1);
                    return [y, y];
                }
                case 'last7':
                    return [this.addDays(today, -6), today];
                case 'last30':
                    return [this.addDays(today, -29), today];
                case 'thisMonth':
                    return [this.startOfMonth(today), today];
                case 'lastMonth': {
                    const start = this.addMonths(this.startOfMonth(today), -1);
                    const end = this.addDays(this.startOfMonth(today), -1);
                    return [start, end];
                }
                default:
                    return [null, null];
            }
        },

        detectPreset(from, to) {
            if (!from && !to) return 'all';

            for (const key of ['today', 'yesterday', 'last7', 'last30', 'thisMonth', 'lastMonth']) {
                const [pf, pt] = this.presetRange(key);
                if (pf && pt && from && to && this.sameDay(pf, from) && this.sameDay(pt, to)) {
                    return key;
                }
            }

            return 'custom';
        },

        syncFromApplied() {
            this.workingFrom = this.parseISO(this.appliedFrom);
            this.workingTo = this.parseISO(this.appliedTo);
            this.activePreset = this.appliedPreset || this.detectPreset(this.workingFrom, this.workingTo);

            const anchor = this.workingFrom || new Date();
            this.leftMonth = this.startOfMonth(anchor);
            this.rightMonth = this.addMonths(this.leftMonth, 1);
        },

        toggle() {
            if (this.open) {
                this.cancel();
                return;
            }

            this.syncFromApplied();
            this.open = true;
            this.updatePosition();
        },

        cancel() {
            this.open = false;
        },

        selectPreset(key) {
            this.activePreset = key;

            if (key === 'custom') {
                this.workingFrom = null;
                this.workingTo = null;
                return;
            }

            const [from, to] = this.presetRange(key);
            this.workingFrom = from;
            this.workingTo = to;

            const anchor = from || new Date();
            this.leftMonth = this.startOfMonth(anchor);
            this.rightMonth = this.addMonths(this.leftMonth, 1);
        },

        shiftMonths(n) {
            this.leftMonth = this.addMonths(this.leftMonth, n);
            this.rightMonth = this.addMonths(this.rightMonth, n);
        },

        selectDay(iso) {
            const date = this.parseISO(iso);
            this.activePreset = 'custom';

            if (!this.workingFrom || (this.workingFrom && this.workingTo)) {
                this.workingFrom = date;
                this.workingTo = null;
            } else if (date < this.workingFrom) {
                this.workingTo = this.workingFrom;
                this.workingFrom = date;
            } else {
                this.workingTo = date;
            }
        },

        weeks(month) {
            const cells = [];
            const first = this.startOfMonth(month);
            const startOffset = first.getDay();
            const daysInMonth = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();

            for (let i = 0; i < startOffset; i++) {
                cells.push({ iso: `blank-${month.getMonth()}-lead-${i}`, day: '', inMonth: false });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const date = new Date(month.getFullYear(), month.getMonth(), d);
                cells.push({ iso: this.toISO(date), day: d, inMonth: true });
            }

            while (cells.length % 7 !== 0) {
                cells.push({ iso: `blank-${month.getMonth()}-trail-${cells.length}`, day: '', inMonth: false });
            }

            return cells;
        },

        dayClasses(cell) {
            if (!cell.inMonth) return 'invisible';

            const date = this.parseISO(cell.iso);
            const isStart = this.sameDay(date, this.workingFrom);
            const isEnd = this.sameDay(date, this.workingTo);
            const inRange = this.workingFrom && this.workingTo && date > this.workingFrom && date < this.workingTo;

            if (isStart || isEnd) return 'bg-primary text-white font-medium';
            if (inRange) return 'bg-primary/10 text-zinc-700';

            return 'text-zinc-700 hover:bg-zinc-100';
        },

        monthLabel(month) {
            return month.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        formatShort(date) {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },

        rangeLabel(from, to) {
            if (!from && !to) return 'All Time';
            if (from && !to) return `${this.formatShort(from)} – Select end date`;
            if (this.sameDay(from, to)) return this.formatShort(from);
            return `${this.formatShort(from)} – ${this.formatShort(to)}`;
        },

        workingLabel() {
            const preset = this.presets.find((p) => p.key === this.activePreset && p.key !== 'custom');
            if (preset) return preset.label;
            return this.rangeLabel(this.workingFrom, this.workingTo);
        },

        triggerLabel() {
            const preset = this.presets.find((p) => p.key === this.appliedPreset && p.key !== 'custom');
            if (preset) return preset.label;
            return this.rangeLabel(this.parseISO(this.appliedFrom), this.parseISO(this.appliedTo));
        },

        apply() {
            const from = this.workingFrom;
            const to = this.workingTo || this.workingFrom;

            this.appliedFrom = from ? this.toISO(from) : '';
            this.appliedTo = to ? this.toISO(to) : '';
            this.appliedPreset = this.activePreset;

            this.$wire.set(this.fromModel, this.appliedFrom);
            this.$wire.set(this.toModel, this.appliedTo);

            this.open = false;
        },
    }));
});
