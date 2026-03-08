// Custom Calendar with Availability Display
class BookingCalendar {
    constructor() {
        this.currentMonth = new Date().getMonth();
        this.currentYear = new Date().getFullYear();
        this.checkinDate = null;
        this.checkoutDate = null;
        
        this.init();
    }
    
    init() {
        this.renderCalendar();
        this.attachEventListeners();
    }
    
    isDateBooked(dateStr) {
        let bookedByPriceLabs = null;
        if (window.priceLabsData && Object.keys(window.priceLabsData).length > 0) {
            const dayData = window.priceLabsData[dateStr];
            if (dayData && Object.prototype.hasOwnProperty.call(dayData, 'available')) {
                bookedByPriceLabs = dayData.available === false || dayData.available === 'false' || dayData.available === 0 || dayData.available === '0';
            }
        }

        const blocked = Array.isArray(window.blockedDates) ? window.blockedDates : [];
        const bookedByIcal = blocked.includes(dateStr);

        if (bookedByPriceLabs === null) {
            return bookedByIcal;
        }

        return bookedByPriceLabs || bookedByIcal;
    }
    
    // Get price for a specific date
    getDatePrice(dateStr) {
        // Try PriceLabs first (using window.priceLabsData for cross-script access)
        if (window.priceLabsData && window.priceLabsData[dateStr] && window.priceLabsData[dateStr].price) {
            return window.priceLabsData[dateStr].price;
        }
        
        // Fallback pricing
        const date = new Date(dateStr + 'T00:00:00');
        const dayOfWeek = date.getDay();
        const isWeekend = dayOfWeek === 5 || dayOfWeek === 6;
        return isWeekend ? 325 : 250;
    }
    
    isDateAvailable(date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return date >= today && !this.isDateBooked(this.formatDate(date));
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    formatDisplayDate(date) {
        const options = { weekday: 'short', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    renderCalendar() {
        const monthYearEl = document.getElementById('calendar-month-year');
        const daysEl = document.getElementById('calendar-days');
        
        if (!monthYearEl || !daysEl) return;
        
        // Set month/year header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        monthYearEl.textContent = `${monthNames[this.currentMonth]} ${this.currentYear}`;
        
        // Clear previous days
        daysEl.innerHTML = '';
        
        // Get first day of month and number of days
        const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        
        // Add empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day empty';
            daysEl.appendChild(emptyDay);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(this.currentYear, this.currentMonth, day);
            const dateStr = this.formatDate(date);
            const dayEl = document.createElement('div');
            dayEl.className = 'calendar-day';
            dayEl.dataset.date = dateStr;
            
            // Create day number element
            const dayNum = document.createElement('span');
            dayNum.className = 'day-num';
            dayNum.textContent = day;
            dayEl.appendChild(dayNum);
            
            // Determine day status
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isBooked = this.isDateBooked(dateStr);
            const isPast = date < today;
            
            if (isPast) {
                dayEl.classList.add('disabled');
            } else if (isBooked) {
                // If check-in is selected and this booked date is AFTER check-in,
                // ONLY show as available-checkout if there are NO booked nights in between
                if (this.checkinDate && !this.checkoutDate && date > this.checkinDate) {
                    if (this.isRangeAvailable(this.checkinDate, date)) {
                        dayEl.classList.add('available-checkout');
                        dayEl.setAttribute('title', 'Available for checkout');
                    } else {
                        dayEl.classList.add('booked');
                    }
                } else {
                    dayEl.classList.add('booked');
                }
            } else {
                dayEl.classList.add('available');
                
                // Add price for available dates
                const price = this.getDatePrice(dateStr);
                if (price) {
                    const priceEl = document.createElement('span');
                    priceEl.className = 'day-price';
                    priceEl.textContent = '$' + price;
                    dayEl.appendChild(priceEl);
                }
            }
            
            // Mark selected dates (check-in and check-out)
            if (this.checkinDate && dateStr === this.formatDate(this.checkinDate)) {
                dayEl.classList.remove('available', 'in-range');
                dayEl.classList.add('selected');
                dayEl.setAttribute('title', 'Check-in');
            }
            if (this.checkoutDate && dateStr === this.formatDate(this.checkoutDate)) {
                dayEl.classList.remove('available', 'in-range');
                dayEl.classList.add('selected');
                dayEl.setAttribute('title', 'Check-out');
            }
            
            // Mark dates in range (between check-in and check-out)
            if (this.checkinDate && this.checkoutDate) {
                if (date > this.checkinDate && date < this.checkoutDate) {
                    if (!dayEl.classList.contains('selected')) {
                        dayEl.classList.add('in-range');
                    }
                }
            }
            
            daysEl.appendChild(dayEl);
        }
    }
    
    attachEventListeners() {
        // Month navigation
        document.getElementById('prev-month')?.addEventListener('click', () => {
            this.currentMonth--;
            if (this.currentMonth < 0) {
                this.currentMonth = 11;
                this.currentYear--;
            }
            this.renderCalendar();
        });
        
        document.getElementById('next-month')?.addEventListener('click', () => {
            this.currentMonth++;
            if (this.currentMonth > 11) {
                this.currentMonth = 0;
                this.currentYear++;
            }
            this.renderCalendar();
        });
        
        // Day selection
        document.getElementById('calendar-days')?.addEventListener('click', (e) => {
            const dayEl = e.target.closest('.calendar-day');
            if (!dayEl ||
                dayEl.classList.contains('empty') ||
                dayEl.classList.contains('disabled')) {
                return; // Ignore invalid clicks
            }
            
            const dateStr = dayEl.dataset.date;
            const selectedDate = new Date(dateStr + 'T00:00:00');
            const isBooked = dayEl.classList.contains('booked');
            const isAvailableCheckout = dayEl.classList.contains('available-checkout');
            
            // If no check-in selected yet, can't select a booked date as check-in
            if (!this.checkinDate && isBooked) {
                alert('This date is not available for check-in. Please select a different date.');
                return;
            }
            
            // If selecting checkout date, allow booked/available-checkout dates
            if (this.checkinDate && !this.checkoutDate) {
                // Allow any date after check-in for checkout (including booked/available-checkout)
                this.handleDateSelection(selectedDate);
            } else if (!isBooked || isAvailableCheckout) {
                // Starting new selection - allow non-booked or available-checkout dates
                this.handleDateSelection(selectedDate);
            } else {
                alert('This date is not available for check-in. Please select a different date.');
            }
        });
    }
    
    handleDateSelection(date) {
        if (!this.checkinDate || (this.checkinDate && this.checkoutDate)) {
            // Start new selection
            this.checkinDate = date;
            this.checkoutDate = null;
        } else if (this.checkinDate && !this.checkoutDate) {
            // Select checkout date
            if (date > this.checkinDate) {
                // Check if any dates in range are booked
                if (this.isRangeAvailable(this.checkinDate, date)) {
                    this.checkoutDate = date;
                } else {
                    alert('Some dates in this range are not available. Please select a different range.');
                    return;
                }
            } else {
                // If earlier date selected, swap to make it checkin
                if (this.isRangeAvailable(date, this.checkinDate)) {
                    this.checkoutDate = this.checkinDate;
                    this.checkinDate = date;
                } else {
                    alert('Some dates in this range are not available. Please select a different range.');
                    return;
                }
            }
        }
        
        this.updateDisplay();
        this.renderCalendar();
        
        // Trigger callback when dates change (for resetting availability button)
        if (this.onDateChange && typeof this.onDateChange === 'function') {
            this.onDateChange();
        }
    }
    
    isRangeAvailable(startDate, endDate) {
        const current = new Date(startDate);
        const end = new Date(endDate);
        
        while (current < end) {
            if (this.isDateBooked(this.formatDate(current))) {
                return false;
            }
            current.setDate(current.getDate() + 1);
        }
        return true;
    }
    
    updateDisplay() {
        const checkinDisplay = document.getElementById('display-checkin');
        const checkoutDisplay = document.getElementById('display-checkout');
        const selectedDatesSection = document.querySelector('.selected-dates');
        
        if (this.checkinDate) {
            checkinDisplay.textContent = this.formatDisplayDate(this.checkinDate);
        } else {
            checkinDisplay.textContent = 'Select date';
        }
        
        if (this.checkoutDate) {
            checkoutDisplay.textContent = this.formatDisplayDate(this.checkoutDate);
            selectedDatesSection.style.display = 'block';
        } else {
            checkoutDisplay.textContent = 'Select date';
        }
        
        if (this.checkinDate || this.checkoutDate) {
            selectedDatesSection.style.display = 'block';
        }
    }
    
    getSelectedDates() {
        return {
            checkin: this.checkinDate,
            checkout: this.checkoutDate
        };
    }
}

// Initialize calendar when modal opens
window.bookingCalendar = null;

// Make BookingCalendar available globally
window.BookingCalendar = BookingCalendar;

function formatIsoDate(date) {
    if (!(date instanceof Date)) {
        return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setBookingValidationState(type, message) {
    const validationEl = document.getElementById('booking-validation');
    if (!validationEl) {
        return;
    }

    validationEl.className = 'booking-alert';
    validationEl.classList.add(type === 'error' ? 'booking-alert-error' : type === 'success' ? 'booking-alert-success' : 'booking-alert-info');
    validationEl.textContent = message;
}

function resetAvailabilityButton() {
    const submitButton = document.getElementById('booking-submit');
    const checkinInput = document.getElementById('booking-checkin-input');
    const checkoutInput = document.getElementById('booking-checkout-input');

    if (!submitButton || !checkinInput || !checkoutInput || !window.bookingCalendar) {
        return;
    }

    const selected = window.bookingCalendar.getSelectedDates();
    const checkin = selected.checkin instanceof Date ? selected.checkin : null;
    const checkout = selected.checkout instanceof Date ? selected.checkout : null;

    checkinInput.value = checkin ? formatIsoDate(checkin) : '';
    checkoutInput.value = checkout ? formatIsoDate(checkout) : '';
    submitButton.disabled = true;

    if (!checkin || !checkout) {
        setBookingValidationState('info', 'Select both check-in and check-out dates to continue.');
        return;
    }

    const millisecondsPerNight = 1000 * 60 * 60 * 24;
    const nights = Math.round((checkout.getTime() - checkin.getTime()) / millisecondsPerNight);
    const checkinIso = formatIsoDate(checkin);
    const dayData = window.priceLabsData && window.priceLabsData[checkinIso] ? window.priceLabsData[checkinIso] : null;
    const dynamicMinStayCandidates = dayData ? [
        dayData.min_stay,
        dayData.minimum_stay,
        dayData.min_nights,
        dayData.minimum_nights
    ] : [];
    let requiredMinStay = Number(window.defaultBookingMinStay || 1);

    dynamicMinStayCandidates.forEach((candidate) => {
        const numeric = Number(candidate);
        if (Number.isFinite(numeric) && numeric > requiredMinStay) {
            requiredMinStay = numeric;
        }
    });

    if (nights < requiredMinStay) {
        setBookingValidationState('error', `This check-in date requires at least ${requiredMinStay} nights.`);
        return;
    }

    submitButton.disabled = false;
    setBookingValidationState('success', `${nights} nights selected. Continue to review your direct-booking checkout.`);
}

window.resetAvailabilityButton = resetAvailabilityButton;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('direct-booking-form')?.addEventListener('submit', (event) => {
        resetAvailabilityButton();
        if (document.getElementById('booking-submit')?.disabled) {
            event.preventDefault();
        }
    });

    // Wait for main.js to load blocked dates and pricing, then initialize calendar
    const initCalendarWhenReady = () => {
        const hasBlockedDates = typeof blockedDates !== 'undefined';
        const hasPriceLabs = typeof priceLabsData !== 'undefined';
        const hasCalendarLoaded = typeof calendarLoaded !== 'undefined';

        if (hasBlockedDates && hasCalendarLoaded) {
            // Initialize calendar
            if (!window.bookingCalendar) {
                window.bookingCalendar = new BookingCalendar();
            } else {
                // Re-render to pick up new data
                window.bookingCalendar.renderCalendar();
            }
            
            // Set up date change callback
            if (typeof resetAvailabilityButton === 'function') {
                window.bookingCalendar.onDateChange = resetAvailabilityButton;
                resetAvailabilityButton();
            }
            
            // Re-render when PriceLabs loads (it loads async after calendar)
            if (!hasPriceLabs) {
                const waitForPriceLabs = setInterval(() => {
                    if (typeof priceLabsData !== 'undefined' && Object.keys(priceLabsData).length > 0) {
                        window.bookingCalendar.renderCalendar();
                        resetAvailabilityButton();
                        clearInterval(waitForPriceLabs);
                    }
                }, 1000);
                
                // Stop waiting after 30 seconds
                setTimeout(() => clearInterval(waitForPriceLabs), 30000);
            }
        } else {
            // Retry after a short delay
            setTimeout(initCalendarWhenReady, 500);
        }
    };
    
    // Start checking after a short delay to let main.js initialize
    setTimeout(initCalendarWhenReady, 500);
});
