document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. SEARCHABLE SERVICES DROPDOWN
    // ==========================================
    const servicesData = [
        { id: 1, name: "Hair Styling" },
        { id: 2, name: "Facial and Skincare" },
        { id: 3, name: "Nail Care" }
    ];

    const searchInput = document.getElementById('serviceSearchInput');
    const dropdownList = document.getElementById('serviceDropdownList');
    const hiddenServiceId = document.getElementById('selectedServiceId');

    // Populate dropdown initially
    renderServices(servicesData);

    searchInput.addEventListener('focus', () => {
        dropdownList.classList.add('active');
    });

    searchInput.addEventListener('input', (e) => {
        const filter = e.target.value.toLowerCase();
        const filtered = servicesData.filter(s => s.name.toLowerCase().includes(filter));
        renderServices(filtered);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#serviceDropdownContainer')) {
            dropdownList.classList.remove('active');
        }
    });

    function renderServices(services) {
        dropdownList.innerHTML = '';
        if (services.length === 0) {
            dropdownList.innerHTML = '<li style="color:#888; font-style:italic;">No services found</li>';
            return;
        }
        services.forEach(service => {
            const li = document.createElement('li');
            li.textContent = service.name;
            li.addEventListener('click', () => {
                searchInput.value = service.name;
                hiddenServiceId.value = service.id;
                dropdownList.classList.remove('active');
            });
            dropdownList.appendChild(li);
        });
    }


    // ==========================================
    // 2. REAL CALENDAR
    // ==========================================
    const calendarMonthYear = document.getElementById('calendarMonthYear');
    const calendarGrid = document.querySelector('.calendar-grid');
    const hiddenDate = document.getElementById('selectedDate');
    
    let currentDate = new Date(); // Today
    let selectedDate = null;

    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        calendarMonthYear.textContent = `${monthNames[month]} ${year}`;

        // Clear existing days (keep the Sun-Sat headers)
        const dayNames = calendarGrid.querySelectorAll('.cal-day-name');
        calendarGrid.innerHTML = '';
        dayNames.forEach(d => calendarGrid.appendChild(d));

        // Get first day of month and total days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0,0,0,0);

        // Add empty slots for days before the 1st
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            calendarGrid.appendChild(empty);
        }

        // Add actual days
        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('day');
            dayDiv.textContent = i;

            const thisDate = new Date(year, month, i);
            
            // Disable past dates
            if (thisDate < today) {
                dayDiv.classList.add('disabled');
            } else {
                // Check if this date is currently selected
                if (selectedDate && thisDate.toDateString() === selectedDate.toDateString()) {
                    dayDiv.classList.add('selected');
                }

                dayDiv.addEventListener('click', () => {
                    selectedDate = thisDate;
                    // Format as YYYY-MM-DD
                    const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    hiddenDate.value = formattedDate;
                    renderCalendar(currentDate); // Re-render to show selection
                });
            }
            calendarGrid.appendChild(dayDiv);
        }
    }

    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });

    renderCalendar(currentDate);


    // ==========================================
    // 3. BOOKING & PROMO AUTO-FILL LOGIC
    // ==========================================
    
    // Helper function to find service ID by name
    function getServiceIdByName(name) {
        const service = servicesData.find(s => s.name === name);
        return service ? service.id : '';
    }

    // Handle "Best Result" Card Book buttons
    const cardBookBtns = document.querySelectorAll('.card-book-btn');
    cardBookBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const serviceName = btn.getAttribute('data-service');
            const serviceId = getServiceIdByName(serviceName);
            
            if (serviceId) {
                searchInput.value = serviceName;
                hiddenServiceId.value = serviceId;
            }

            // Scroll to top so user can pick date/time
            document.querySelector('.search-section').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Config block for Promos
    // REPLACE VALUES HERE if your services change
    const PROMOS = {
        metime:  { service: "Facial and Skincare", people: "1", priceTier: "500-1000" },
        couple:  { service: "Facial and Skincare", people: "2", priceTier: "over-1000" },
        wedding: { service: "Hair Styling",         people: "3", priceTier: "over-1000" }
    };

    const promoBookBtns = document.querySelectorAll('.promo-book-btn');
    promoBookBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const promoKey = btn.getAttribute('data-promo');
            const config = PROMOS[promoKey];

            if (config) {
                // Set Service
                const serviceId = getServiceIdByName(config.service);
                if (serviceId) {
                    searchInput.value = config.service;
                    hiddenServiceId.value = serviceId;
                }

                // Set People Dropdown
                document.getElementById('peopleFilter').value = config.people;

                // Set Price Dropdown
                document.getElementById('priceFilter').value = config.priceTier;
            }

            // Scroll to top so user can pick date/time
            document.querySelector('.search-section').scrollIntoView({ behavior: 'smooth' });
        });
    });

});