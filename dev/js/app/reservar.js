document.addEventListener("DOMContentLoaded", function() {
    if (document.body.classList.contains('page-template-tema-home-fase-2')) {
        const reservaForm = document.getElementById('form-reserva');
        const childrenInput = document.getElementById('children');
        const childrenAgesDiv = document.getElementById('children-ages');

        childrenInput.addEventListener('change', function() {
            const numChildren = parseInt(this.value);
            childrenAgesDiv.innerHTML = '';
            if (numChildren > 0) {
                childrenAgesDiv.style.display = 'flex';
                for (let i = 1; i <= numChildren; i++) {
                    const inputBox = document.createElement('div');
                    inputBox.className = 'input-box';

                    const ageLabel = document.createElement('label');
                    ageLabel.className = 'label';
                    ageLabel.textContent = `Idade Criança ${i}`;
                    const ageSelect = document.createElement('select');
                    ageSelect.className = 'input fixed-label';
                    ageSelect.name = `child_age_${i}`;
                    ageSelect.required = true;

                    for (let j = 0; j <= 17; j++) {
                        const option = document.createElement('option');
                        option.value = j;
                        option.textContent = j + " anos";
                        ageSelect.appendChild(option);
                    }

                    inputBox.appendChild(ageSelect);
                    inputBox.appendChild(ageLabel);
                    childrenAgesDiv.appendChild(inputBox);
                }
            } else {
                childrenAgesDiv.style.display = 'none';
            }
        });

        reservaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const checkInField = document.getElementById('checkin');
            const checkOutField = document.getElementById('checkout');
            const checkIn = document.getElementById('checkin').value;
            const checkOut = document.getElementById('checkout').value;
            const rooms = document.getElementById('rooms').value;
            const adults = document.getElementById('adults').value;
            const children = document.getElementById('children').value;

            if(!checkIn)
            {
                checkInField.classList.add('error');
                checkInField.focus();
                return false;
            }
            if(!checkOut)
            {
                checkOutField.classList.add('error');
                checkOutField.focus();
                return false;
            }

            let url = `https://book.omnibees.com/hotelresults?c=8274&q=3159&hotel_folder=&NRooms=${rooms}&CheckIn=${formatDate(checkIn)}&CheckOut=${formatDate(checkOut)}&ad=${adults}&ch=${children}`;

            if (children > 0) {
                let ages = [];
                for (let i = 1; i <= children; i++) {
                    const ageSelect = document.querySelector(`select[name="child_age_${i}"]`);
                    ages.push(ageSelect.value);
                }
                url += `&ag=${ages.join(';')}`;
            }

            url += '&Code=BF24&group_code=&loyalty_code=&lang=pt-BR&currencyId=16';
            console.log(url);
            window.location.href = url;
        });

        function formatDate(dateStr) {
            const parts = dateStr.split('/');
            const retorno = parts[0] + parts[1] + parts[2];
            console.log(retorno);
            return retorno;
        }

        // Inicializar o Flatpickr no campo de Check-in
        flatpickr("#checkin", {
            dateFormat: "d/m/Y",
            minDate: "today",
            maxDate: "30/12/2025",
            locale: "pt",
            onChange: function(selectedDates, dateStr) {
                // Atualizar a data mínima do Check-out com base no Check-in
                checkoutCalendar.set('minDate', dateStr);
            }
        });

        // Inicializar o Flatpickr no campo de Check-out
        const checkoutCalendar = flatpickr("#checkout", {
            dateFormat: "d/m/Y",
            minDate: "today",
            maxDate: "31/12/2025",
            locale: "pt"
        });
    }
});

(function($) {
    $(document).ready(function() {
        if (document.body.classList.contains('page-template-tema-home-fase-2'))
        {
            
        } // if document.body.classList.contains('page-template-tema-home-fase-2')
    });
})(jQuery);