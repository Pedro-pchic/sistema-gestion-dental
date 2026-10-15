const form = document.querySelector('[data-booking-form]');

if (form) {
    const treatment = form.querySelector('#treatment_id');
    const dentist = form.querySelector('#dentist_id');
    const date = form.querySelector('#date');
    const time = form.querySelector('#time');
    const days = form.querySelector('[data-calendar-days]');
    const calendarTitle = form.querySelector('[data-calendar-title]');
    const calendarStatus = form.querySelector('[data-calendar-status]');
    const previous = form.querySelector('[data-previous-month]');
    const next = form.querySelector('[data-next-month]');
    const selectedDate = form.querySelector('[data-selected-date]');
    const slotMessage = form.querySelector('[data-slot-message]');
    const timeOptions = form.querySelector('[data-time-options]');
    const summary = form.querySelector('[data-booking-summary]');
    const submit = form.querySelector('[data-booking-submit]');
    const today = form.dataset.today;
    const maxDate = form.dataset.maxDate;
    const firstMonth = today.slice(0, 7);
    const lastMonth = maxDate.slice(0, 7);
    let month = date.value && date.value >= today && date.value <= maxDate ? date.value.slice(0, 7) : firstMonth;
    let monthRequest = 0;
    let dayRequest = 0;
    let availabilityVerified = false;

    const params = (key, value) => new URLSearchParams({ treatment_id: treatment.value, dentist_id: dentist.value, [key]: value });
    const ready = () => treatment.value && dentist.value;
    const dayLabel = value => new Intl.DateTimeFormat('es-GT', { weekday: 'long', day: 'numeric', month: 'long' })
        .format(new Date(`${value}T12:00:00`));

    function updateSummary() {
        const parts = [treatment.selectedOptions[0]?.textContent, dentist.selectedOptions[0]?.textContent, date.value && dayLabel(date.value), time.value]
            .filter(value => value && !value.startsWith('Selecciona'));
        summary.textContent = parts.length === 4 ? parts.join(' · ') : 'Selecciona servicio, odontólogo, fecha y hora.';
        submit.disabled = parts.length !== 4 || !availabilityVerified;
    }

    function clearTime(message = 'Selecciona una fecha disponible.', invalidateRequest = true) {
        if (invalidateRequest) dayRequest++;
        availabilityVerified = false;
        time.value = '';
        timeOptions.replaceChildren();
        slotMessage.textContent = message;
        updateSummary();
    }

    function updateNavigation() {
        previous.disabled = month <= firstMonth;
        next.disabled = month >= lastMonth;
        calendarTitle.textContent = new Intl.DateTimeFormat('es-GT', { month: 'long', year: 'numeric' })
            .format(new Date(`${month}-01T12:00:00`));
    }

    function renderDays(availability = {}) {
        days.replaceChildren();
        const [year, monthNumber] = month.split('-').map(Number);
        const first = new Date(year, monthNumber - 1, 1);
        const offset = (first.getDay() + 6) % 7;
        const count = new Date(year, monthNumber, 0).getDate();

        for (let index = 0; index < offset; index++) {
            days.append(document.createElement('span'));
        }

        for (let number = 1; number <= count; number++) {
            const value = `${month}-${String(number).padStart(2, '0')}`;
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = number;
            button.setAttribute('aria-label', dayLabel(value));
            button.setAttribute('aria-pressed', String(date.value === value));
            button.disabled = !availability[value];
            button.className = 'aspect-square rounded-xl text-sm font-semibold disabled:cursor-not-allowed disabled:text-slate-400 hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-brand-700';
            if (availability[value]) {
                button.classList.add(date.value === value ? 'bg-brand-700' : 'bg-brand-50', date.value === value ? 'text-white' : 'text-brand-900');
                button.addEventListener('click', () => {
                    date.value = value;
                    renderDays(availability);
                    loadTimes();
                });
            }
            days.append(button);
        }
    }

    async function loadTimes() {
        const request = ++dayRequest;
        const previousTime = time.value;
        clearTime('Consultando horarios…', false);
        selectedDate.textContent = date.value ? dayLabel(date.value) : 'la fecha seleccionada';
        if (!date.value || !ready()) return;

        try {
            const response = await fetch(`${form.dataset.slotsUrl}?${params('date', date.value)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudo consultar la disponibilidad.');
            const result = await response.json();
            if (request !== dayRequest) return;

            const available = result.times.filter(slot => slot.available);
            if (available.length === 0) {
                slotMessage.textContent = 'No quedan horarios disponibles para esta fecha. Elige otro día.';
                date.value = '';
                selectedDate.textContent = 'la fecha seleccionada';
                updateSummary();
                loadMonth();
                return;
            }

            slotMessage.textContent = 'Los horarios ocupados no se pueden seleccionar.';
            for (const slot of result.times) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = slot.available ? slot.time : `${slot.time} · Ocupado`;
                button.disabled = !slot.available;
                button.setAttribute('aria-pressed', String(slot.time === previousTime && slot.available));
                button.className = 'rounded-xl border px-2 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400';
                if (slot.available) {
                    button.classList.add(slot.time === previousTime ? 'border-brand-700' : 'border-brand-200', slot.time === previousTime ? 'bg-brand-700' : 'bg-white', slot.time === previousTime ? 'text-white' : 'text-brand-800');
                    button.addEventListener('click', () => {
                        time.value = slot.time;
                        for (const option of timeOptions.querySelectorAll('button:not(:disabled)')) {
                            const selected = option === button;
                            option.setAttribute('aria-pressed', String(selected));
                            option.classList.toggle('bg-brand-700', selected);
                            option.classList.toggle('text-white', selected);
                            option.classList.toggle('bg-white', !selected);
                            option.classList.toggle('text-brand-800', !selected);
                            option.classList.toggle('border-brand-700', selected);
                            option.classList.toggle('border-brand-200', !selected);
                        }
                        availabilityVerified = true;
                        updateSummary();
                    });
                }
                timeOptions.append(button);
            }
            if (available.some(slot => slot.time === previousTime)) {
                time.value = previousTime;
                availabilityVerified = true;
            }
            else if (previousTime) slotMessage.textContent = 'El horario seleccionado ya no está disponible. Elige otro.';
            updateSummary();
        } catch {
            if (request === dayRequest) slotMessage.textContent = 'No pudimos cargar los horarios. Selecciona la fecha para intentar de nuevo.';
        }
    }

    async function loadMonth() {
        const request = ++monthRequest;
        updateNavigation();
        renderDays();
        calendarStatus.textContent = ready() ? 'Consultando fechas disponibles…' : 'Selecciona un servicio y un odontólogo para consultar fechas.';
        if (!ready()) return;

        try {
            const response = await fetch(`${form.dataset.slotsUrl}?${params('month', month)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudo consultar la disponibilidad.');
            const result = await response.json();
            if (request !== monthRequest) return;
            renderDays(result.days);
            calendarStatus.textContent = Object.values(result.days).some(Boolean)
                ? 'Selecciona uno de los días disponibles.'
                : 'No hay fechas disponibles este mes.';
            if (date.value && date.value.slice(0, 7) === month) {
                if (result.days[date.value]) loadTimes();
                else {
                    date.value = '';
                    selectedDate.textContent = 'la fecha seleccionada';
                    clearTime('La fecha elegida ya no está disponible. Selecciona otra.');
                }
            }
        } catch {
            if (request === monthRequest) calendarStatus.textContent = 'No pudimos cargar las fechas. Cambia de mes para intentar de nuevo.';
        }
    }

    for (const select of [treatment, dentist]) {
        select.addEventListener('change', () => {
            date.value = '';
            clearTime();
            loadMonth();
        });
    }
    for (const [button, increment] of [[previous, -1], [next, 1]]) {
        button.addEventListener('click', () => {
            const [year, monthNumber] = month.split('-').map(Number);
            const target = new Date(year, monthNumber - 1 + increment, 1);
            month = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}`;
            date.value = '';
            clearTime();
            loadMonth();
        });
    }

    updateSummary();
    loadMonth();
    if (document.querySelector('#cita [role="alert"]')) location.hash = 'cita';
}
