(function () {
  const calendarSlots = document.querySelectorAll('[data-slot]');
  const modal = document.getElementById('reservationModal');
  const form = document.getElementById('reservationForm');
  const dateInput = document.getElementById('reservationDateTime');
  const noteInput = document.getElementById('reservationNote');
  const closeButtons = document.querySelectorAll('[data-close-modal]');
  const messageArea = document.querySelector('.calendar-message');
  const refreshButton = document.querySelector('[data-calendar-refresh]');

  if (!form || !modal || !dateInput) {
    return;
  }

  const endpoint = form.dataset.endpoint || form.getAttribute('action') || 'reservation_calendar_api.php';
  const room = form.dataset.room || '';

  function setMessage(text, isError = false) {
    if (!messageArea) {
      return;
    }
    messageArea.textContent = text;
    messageArea.classList.toggle('calendar-message--error', isError);
    if (text) {
      messageArea.classList.add('is-visible');
    } else {
      messageArea.classList.remove('is-visible');
    }
  }

  function openModal(datetimeValue) {
    dateInput.value = datetimeValue || '';
    noteInput.value = '';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    window.setTimeout(() => {
      dateInput.focus();
    }, 50);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  calendarSlots.forEach((slot) => {
    slot.addEventListener('click', () => {
      const status = slot.dataset.status;
      if (status !== 'available') {
        return;
      }
      const datetime = slot.dataset.datetime || '';
      openModal(datetime);
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      closeModal();
    });
  });

  if (refreshButton) {
    refreshButton.addEventListener('click', () => {
      window.location.reload();
    });
  }

  modal.addEventListener('click', (event) => {
    if (event.target && event.target.matches('.reservation-modal__overlay')) {
      closeModal();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const reservedAt = dateInput.value.trim();
    const note = noteInput.value.trim();
    if (!reservedAt) {
      setMessage('予約日時を入力してください。', true);
      return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = '保存中...';
    }
    setMessage('予約を登録しています...');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          room,
          reserved_at: reservedAt,
          note,
        }),
      });

      const payload = await response.json();
      if (!response.ok || !payload.success) {
        const message = payload && payload.error ? payload.error : '予約の登録に失敗しました。';
        setMessage(message, true);
        return;
      }

      setMessage(payload.message || '予約を登録しました。');
      closeModal();
      window.setTimeout(() => {
        window.location.reload();
      }, 1200);
    } catch (error) {
      setMessage('通信中にエラーが発生しました。時間をおいて再度お試しください。', true);
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = '保存';
      }
    }
  });
})();
