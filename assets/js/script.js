document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-confirm]');
  if (button && !window.confirm(button.dataset.confirm)) event.preventDefault();
});

document.addEventListener('DOMContentLoaded', function () {
  const service = document.querySelector('[data-service-price]');
  const price = document.querySelector('[data-price-output]');
  const staff = document.querySelector('[data-staff-select]');
  if (service && price) {
    const updateBooking = () => {
      const selected = service.options[service.selectedIndex];
      price.value = selected && selected.dataset.price ? 'R ' + Number(selected.dataset.price).toFixed(2) : '';
      const summaryName = document.querySelector('[data-summary-name]');
      const summaryDetail = document.querySelector('[data-summary-detail]');
      const summaryPrice = document.querySelector('[data-summary-price]');
      if (summaryName && summaryDetail && summaryPrice) {
        summaryName.textContent = selected && selected.dataset.name ? selected.dataset.name : 'Select a service to see the details';
        summaryDetail.textContent = selected && selected.dataset.duration ? selected.dataset.duration + ' minutes · Price retrieved from GlowHub' : 'Price and duration will appear here.';
        summaryPrice.textContent = selected && selected.dataset.price ? 'R ' + Number(selected.dataset.price).toFixed(2) : '—';
      }
      if (staff) {
        const serviceId = service.value;
        Array.from(staff.options).forEach((option, index) => {
          if (index === 0) return;
          const skills = (option.dataset.services || '').split(',');
          option.disabled = !serviceId || !skills.includes(serviceId);
        });
        if (staff.selectedOptions[0] && staff.selectedOptions[0].disabled) staff.value = '';
      }
    };
    service.addEventListener('change', updateBooking);
    updateBooking();
  }

  const appointment = document.querySelector('[data-payment-appointment]');
  const amount = document.querySelector('[data-payment-amount]');
  if (appointment && amount) {
    const updateAmount = () => {
      const selected = appointment.options[appointment.selectedIndex];
      if (selected && selected.dataset.price) amount.value = Number(selected.dataset.price).toFixed(2);
    };
    appointment.addEventListener('change', updateAmount);
    updateAmount();
  }
});
