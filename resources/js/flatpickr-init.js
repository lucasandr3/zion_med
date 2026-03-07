/**
 * Inicializa Flatpickr em inputs com classes .flatpickr-date e .flatpickr-time.
 * Uso: adicione a classe ao input e opcionalmente data-flatpickr-* para opções.
 */
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import 'flatpickr/dist/themes/light.css';

const defaultDateOptions = {
  locale: Portuguese,
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'd/m/Y',
  altInputClass: 'form-input flatpickr-alt',
  allowInput: true,
};

const defaultTimeOptions = {
  enableTime: true,
  noCalendar: true,
  dateFormat: 'H:i',
  time_24hr: true,
  locale: Portuguese,
  allowInput: true,
  minuteIncrement: 5,
};

function initFlatpickr() {
  document.querySelectorAll('.flatpickr-date').forEach((el) => {
    if (el._flatpickr) return;
    flatpickr(el, { ...defaultDateOptions, ...(el.dataset.flatpickrOptions ? JSON.parse(el.dataset.flatpickrOptions) : {}) });
  });

  document.querySelectorAll('.flatpickr-time').forEach((el) => {
    if (el._flatpickr) return;
    const initialVal = (el.value || '').trim();
    const timeOptions = { ...defaultTimeOptions };
    if (initialVal && /^\d{1,2}:\d{2}$/.test(initialVal)) {
      const [h, m] = initialVal.split(':').map(Number);
      timeOptions.defaultHour = h;
      timeOptions.defaultMinute = m;
    }
    flatpickr(el, { ...timeOptions, ...(el.dataset.flatpickrOptions ? JSON.parse(el.dataset.flatpickrOptions) : {}) });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFlatpickr);
} else {
  initFlatpickr();
}

export { initFlatpickr };
