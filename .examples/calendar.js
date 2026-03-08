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
        // Check PriceLabs data first (most accurate)
        if (window.priceLabsData && Object.keys(window.priceLabsData).length > 0) {
            const dayData = window.priceLabsData[dateStr];
            if (dayData) {
                // If PriceLabs says not available, it's booked
                if (dayData.available === false) {
                    return true;
                }
                // If PriceLabs says available, it's not booked
                return false;
            }
        }
        
        // Fallback to Airbnb iCal blockedDates
        if (typeof blockedDates !== 'undefined' && blockedDates.length > 0) {
            return blockedDates.includes(dateStr);
        }
        return false;
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
            if (!e.target.classList.contains('calendar-day') || 
                e.target.classList.contains('empty') ||
                e.target.classList.contains('disabled')) {
                return; // Ignore invalid clicks
            }
            
            const dateStr = e.target.dataset.date;
            const selectedDate = new Date(dateStr + 'T00:00:00');
            const isBooked = e.target.classList.contains('booked');
            const isAvailableCheckout = e.target.classList.contains('available-checkout');
            
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

document.addEventListener('DOMContentLoaded', () => {
    // Wait for main.js to load blocked dates and pricing, then initialize calendar
    const initCalendarWhenReady = () => {
        const hasBlockedDates = typeof blockedDates !== 'undefined';
        const hasPriceLabs = typeof priceLabsData !== 'undefined';
        const hasCalendarLoaded = typeof calendarLoaded !== 'undefined';
        
        console.log('Calendar check: blockedDates=' + hasBlockedDates + ', priceLabsData=' + hasPriceLabs + ', calendarLoaded=' + hasCalendarLoaded);
        
        if (hasBlockedDates && hasCalendarLoaded) {
            console.log('✓ Calendar integration ready');
            console.log('  - Blocked dates: ' + (blockedDates ? blockedDates.length : 0));
            console.log('  - PriceLabs data: ' + (hasPriceLabs ? Object.keys(priceLabsData).length + ' days' : 'not loaded'));
            
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
            }
            
            // Re-render when PriceLabs loads (it loads async after calendar)
            if (!hasPriceLabs) {
                const waitForPriceLabs = setInterval(() => {
                    if (typeof priceLabsData !== 'undefined' && Object.keys(priceLabsData).length > 0) {
                        console.log('✓ PriceLabs data loaded - refreshing calendar');
                        window.bookingCalendar.renderCalendar();
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
