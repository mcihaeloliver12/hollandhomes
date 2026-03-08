document.addEventListener('DOMContentLoaded', function() {

    // Throttle function to limit scroll event firing
    function throttle(func, delay) {
        let timeoutId;
        let lastRan;
        return function() {
            const context = this;
            const args = arguments;
            if (!lastRan) {
                func.apply(context, args);
                lastRan = Date.now();
            } else {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    if ((Date.now() - lastRan) >= delay) {
                        func.apply(context, args);
                        lastRan = Date.now();
                    }
                }, delay - (Date.now() - lastRan));
            }
        };
    }

    // Sticky Header with parallax effect
    const header = document.getElementById('main-header');
    const bookingBar = document.getElementById('sticky-booking-bar');
    const hero = document.getElementById('hero');
    
    const handleScroll = function() {
        const scrolled = window.scrollY;
        
        // Header styling with reduced opacity when scrolled
        if (scrolled > 50) {
            header.style.backgroundColor = 'rgba(245, 243, 239, 0.3)';
            header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            header.style.backdropFilter = 'blur(10px)';
        } else {
            header.style.backgroundColor = 'rgba(245, 243, 239, 0.9)';
            header.style.boxShadow = 'none';
            header.style.backdropFilter = 'none';
        }
        
        // Show sticky booking bar after scrolling past hero, hide near footer
        const footer = document.querySelector('footer');
        const footerTop = footer ? footer.getBoundingClientRect().top + scrolled : Infinity;
        
        if (bookingBar && scrolled > window.innerHeight * 0.75 && scrolled < footerTop - 100) {
            bookingBar.classList.add('visible');
        } else if (bookingBar) {
            bookingBar.classList.remove('visible');
        }
        
        // Parallax effect for hero content
        if (hero && scrolled < window.innerHeight) {
            const heroContent = hero.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.transform = `translateY(${scrolled * 0.5}px)`;
                heroContent.style.opacity = 1 - (scrolled / window.innerHeight);
            }
        }
    };
});

 

function countAvailableDatesThisMonth() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const today = now.getDate();
    const lastDay = new Date(year, month + 1, 0).getDate();

    let count = 0;

    for (let d = today; d <= lastDay; d++) {
        const date = new Date(year, month, d);
        const dateStr = formatDate(date);

        if (calendarLoaded) {
            if (!blockedDates.includes(dateStr)) {
                count++;
            }
        } else if (priceLabsLoaded) {
            const day = priceLabsData[dateStr];
            if (day && day.available) {
                count++;
            }
        } else {
            // No data yet
            return null;
        }
    }

    return count;
}

function updateStickyBarAvailability() {
    const bar = document.getElementById('sticky-booking-bar');
    if (!bar) return;
    const textEl = bar.querySelector('.urgency-text');
    if (!textEl) return;

    const count = countAvailableDatesThisMonth();
    if (count === null) {
        // Keep existing text if no data yet
        return;
    }

    if (count <= 0) {
        textEl.textContent = 'Fully booked this month';
    } else if (count === 1) {
        textEl.textContent = 'Only 1 date remaining this month';
    } else {
        textEl.textContent = `Only ${count} dates remaining this month`;
    }
}

document.addEventListener('DOMContentLoaded', function() {

    // Sticky Header with parallax effect
    const header = document.getElementById('main-header');
    const bookingBar = document.getElementById('sticky-booking-bar');
    const hero = document.getElementById('hero');
    
    const handleScroll = function() {
        const scrolled = window.scrollY;
        
        // Header styling with reduced opacity when scrolled
        if (scrolled > 50) {
            header.style.backgroundColor = 'rgba(245, 243, 239, 0.3)';
            header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            header.style.backdropFilter = 'blur(10px)';
        } else {
            header.style.backgroundColor = 'rgba(245, 243, 239, 0.9)';
            header.style.boxShadow = 'none';
            header.style.backdropFilter = 'none';
        }
        
        // Show sticky booking bar after scrolling past hero, hide near footer
        const footer = document.querySelector('footer');
        const footerTop = footer ? footer.getBoundingClientRect().top + scrolled : Infinity;
        
        if (bookingBar && scrolled > window.innerHeight * 0.75 && scrolled < footerTop - 100) {
            bookingBar.classList.add('visible');
        } else if (bookingBar) {
            bookingBar.classList.remove('visible');
        }
        
        // Parallax effect for hero content
        if (hero && scrolled < window.innerHeight) {
            const heroContent = hero.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.transform = `translateY(${scrolled * 0.5}px)`;
                heroContent.style.opacity = 1 - (scrolled / window.innerHeight);
            }
        }
    };
    
    // Scroll listener
    window.addEventListener('scroll', handleScroll, { passive: true });

    // Fade-in Sections
    const sections = document.querySelectorAll('.fade-in-section');

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    sections.forEach(section => {
        observer.observe(section);
    });

    // Set minimum date for date pickers to today
    const today = new Date().toISOString().split('T')[0];
    const checkinInput = document.getElementById('checkin-date');
    const checkoutInput = document.getElementById('checkout-date');
    
    if (checkinInput) checkinInput.setAttribute('min', today);
    if (checkoutInput) checkoutInput.setAttribute('min', today);

    // Update checkout minimum when checkin changes & reset pricing
    if (checkinInput) {
        checkinInput.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            const minCheckout = new Date(checkinDate);
            minCheckout.setDate(minCheckout.getDate() + 1);
            checkoutInput.setAttribute('min', minCheckout.toISOString().split('T')[0]);
            
            // Reset availability button and pricing when dates change
            if (typeof resetAvailabilityButton === 'function') {
                resetAvailabilityButton();
            }
        });
    }
    
    // Reset pricing when checkout date changes
    if (checkoutInput) {
        checkoutInput.addEventListener('change', function() {
            // Reset availability button and pricing when dates change
            if (typeof resetAvailabilityButton === 'function') {
                resetAvailabilityButton();
            }
        });
    }

    // FAQ Accordion (homepage only) - avoid conflicting with dedicated FAQ page
    const isFaqPage = document.querySelector('main.faq-page');
    if (!isFaqPage) {
        const faqQuestions = document.querySelectorAll('#faq .faq-question');
        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                const faqItem = this.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                // Close all other FAQs in the homepage section
                document.querySelectorAll('#faq .faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Toggle current FAQ
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });
    }

    // AUTOMATIC CALENDAR SYNC - Fetch ASAP to update sticky bar availability
    fetchBlockedDates().then(() => {
        updateStickyBarAvailability();
    });

    // DYNAMIC GALLERY - Load from database if available (defer to improve initial load)
    if ('requestIdleCallback' in window) {
        requestIdleCallback(function() {
            loadGalleryImages();
        });
    } else {
        setTimeout(loadGalleryImages, 1000);
    }

});

// Booking Modal Functions
function openBookingModal() {
    document.getElementById('booking-modal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Reset the availability button when modal opens
    resetAvailabilityButton();
    
    // Set up calendar date change handler
    if (window.bookingCalendar) {
        window.bookingCalendar.onDateChange = resetAvailabilityButton;
    }
}

function closeBookingModal() {
    document.getElementById('booking-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Reset availability button when dates change
function resetAvailabilityButton() {
    // Find the availability button in the modal
    const modal = document.getElementById('booking-modal');
    if (!modal) return;
    
    // Try multiple selectors for compatibility with old and new designs
    let button = modal.querySelector('button[onclick*="checkAvailability"]');
    if (!button) button = modal.querySelector('button.btn-primary');
    if (!button) button = modal.querySelector('.modal-content button');
    
    const pricingDisplay = document.getElementById('pricing-display');
    
    if (button) {
        button.textContent = 'Check Availability & Pricing';
        button.disabled = false;
        button.onclick = function(event) { checkAvailability(event); };
    }
    
    if (pricingDisplay) {
        pricingDisplay.style.display = 'none';
    }
    
    console.log('Availability button reset - dates changed');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('booking-modal');
    if (event.target == modal) {
        closeBookingModal();
    }
}

// AUTOMATIC CALENDAR SYNC - Fetches blocked dates directly from Airbnb
let blockedDates = [];
let calendarLoaded = false;

// Your Airbnb iCal URL (public, safe to use here)
const AIRBNB_ICAL_URL = 'https://www.airbnb.com/calendar/ical/854400091154887268.ics?s=bf4b974c75df0c847bf301600694467c';

// PRICING CONFIGURATION
const CLEANING_FEE = 125;

// PriceLabs Integration
const PRICELABS_PROXY_URL = '/api/pricelabs-proxy.php'; // Your PHP proxy endpoint
window.priceLabsData = {}; // Store pricing data from PriceLabs (global for calendar.js access)
var priceLabsData = window.priceLabsData; // Local reference
let priceLabsLoaded = false;

// Fallback Smart Pricing Rules (used if PriceLabs fails)
const PRICING_RULES_FALLBACK = {
    weekday: 250,
    weekend: 325,
    peak: 375,
    peakSeasons: [
        { start: '2025-12-20', end: '2026-01-05' }, // Christmas/New Year
        { start: '2025-11-27', end: '2025-11-30' }, // Thanksgiving
        { start: '2025-07-01', end: '2025-08-31' }, // Summer
        { start: '2025-12-26', end: '2026-01-01' }  // New Year
    ]
};

// Parse iCal date format (YYYYMMDD)
function parseIcalDate(dateStr) {
    dateStr = dateStr.replace(/;?VALUE=DATE:?/, '');
    const year = parseInt(dateStr.substring(0, 4));
    const month = parseInt(dateStr.substring(4, 6)) - 1;
    const day = parseInt(dateStr.substring(6, 8));
    return new Date(year, month, day);
}

// Parse iCal VEVENTS into an array of YYYY-MM-DD booked dates
function parseIcalDates(icalText) {
    const dates = [];
    const lines = icalText.split(/\r?\n/);

    let inEvent = false;
    let eventStart = null;
    let eventEnd = null;

    for (let line of lines) {
        line = line.trim();

        if (line === 'BEGIN:VEVENT') {
            inEvent = true;
            eventStart = null;
            eventEnd = null;
        } else if (line === 'END:VEVENT' && inEvent) {
            if (eventStart && eventEnd) {
                const start = parseIcalDate(eventStart);
                const end = parseIcalDate(eventEnd);

                if (start && end) {
                    let current = new Date(start);
                    const endDate = new Date(end);

                    while (current < endDate) {
                        dates.push(formatDate(current));
                        current.setDate(current.getDate() + 1);
                    }
                }
            }
            inEvent = false;
        } else if (inEvent) {
            if (line.startsWith('DTSTART')) {
                eventStart = line.split(':')[1];
            } else if (line.startsWith('DTEND')) {
                eventEnd = line.split(':')[1];
            }
        }
    }

    return dates;
}

// Format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Fetch PriceLabs pricing data from API via proxy
async function fetchPriceLabsPricing() {
    try {
        console.log('Fetching pricing from PriceLabs API...');
        
        const today = new Date();
        const oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(today.getFullYear() + 1);
        
        const response = await fetch(PRICELABS_PROXY_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                dateFrom: formatDate(today),
                dateTo: formatDate(oneYearFromNow)
            })
        });
        
        if (!response.ok) {
            throw new Error(`PriceLabs API error: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('PriceLabs API response:', result);
        
        if (result.success && result.data) {
            // Store data globally
            window.priceLabsData = result.data;
            window.priceLabsLoaded = true;
            priceLabsLoaded = true;
            
            // Log summary
            const totalDays = Object.keys(result.data).length;
            const bookedDays = result.bookedDays || 0;
            const availableDays = result.availableDays || 0;
            
            console.log(`✓ Loaded PriceLabs pricing: ${totalDays} days total`);
            console.log(`  - Available: ${availableDays} days`);
            console.log(`  - Booked: ${bookedDays} days`);
            console.log(`✓ Last refreshed: ${result.lastRefreshed || 'N/A'}`);
            
            // Sample first few prices for debugging
            const dates = Object.keys(result.data).slice(0, 3);
            dates.forEach(date => {
                const day = result.data[date];
                console.log(`  Sample ${date}: $${day.price}, available: ${day.available}`);
            });
            
            return true;
        } else {
            throw new Error(result.error || 'Invalid PriceLabs response');
        }
    } catch (error) {
        console.warn('⚠️ PriceLabs API failed, using fallback pricing:', error.message);
        console.warn('  Full error:', error);
        window.priceLabsLoaded = false;
        priceLabsLoaded = false;
        return false;
    }
}

// Get nightly rate - uses PriceLabs if available, otherwise fallback pricing
function getNightlyRate(date) {
    const dateStr = formatDate(new Date(date));
    
    // Try PriceLabs first
    if (priceLabsLoaded && priceLabsData[dateStr]) {
        return priceLabsData[dateStr].price;
    }
    
    // Fallback to smart pricing rules
    const dateObj = new Date(date);
    const dayOfWeek = dateObj.getDay();
    
    const isPeakSeason = PRICING_RULES_FALLBACK.peakSeasons.some(season => {
        return dateStr >= season.start && dateStr <= season.end;
    });
    
    if (isPeakSeason) {
        return PRICING_RULES_FALLBACK.peak;
    }
    
    const isWeekend = dayOfWeek === 5 || dayOfWeek === 6;
    return isWeekend ? PRICING_RULES_FALLBACK.weekend : PRICING_RULES_FALLBACK.weekday;
}

// Fetch blocked dates and pricing - called on page load
async function fetchBlockedDates() {
    try {
        console.log('Fetching blocked dates from Airbnb calendar...');
        
        // Try multiple CORS proxies in case one fails
        const corsProxies = [
            'https://api.allorigins.win/raw?url=',
            'https://corsproxy.io/?',
            'https://cors-anywhere.herokuapp.com/'
        ];
        
        let icalText = null;
        
        for (const proxyUrl of corsProxies) {
            try {
                console.log('Trying proxy:', proxyUrl.substring(0, 30) + '...');
                const response = await fetch(proxyUrl + encodeURIComponent(AIRBNB_ICAL_URL), {
                    timeout: 10000
                });
                
                if (response.ok) {
                    icalText = await response.text();
                    if (icalText && icalText.includes('VCALENDAR')) {
                        console.log('✓ Successfully fetched via proxy');
                        break;
                    }
                }
            } catch (e) {
                console.warn('Proxy failed:', e.message);
            }
        }
        
        if (icalText && icalText.includes('VCALENDAR')) {
            blockedDates = parseIcalDates(icalText);
            calendarLoaded = true;
            console.log(`✓ Loaded ${blockedDates.length} blocked dates from Airbnb`);
        } else {
            throw new Error('Could not fetch calendar from any proxy');
        }
        
        // Also fetch PriceLabs pricing
        await fetchPriceLabsPricing();
        
        if (priceLabsLoaded) {
            console.log('✓ Using PriceLabs dynamic pricing');
        } else {
            console.log('✓ Using fallback pricing: Weekday $' + PRICING_RULES_FALLBACK.weekday + ', Weekend $' + PRICING_RULES_FALLBACK.weekend + ', Peak $' + PRICING_RULES_FALLBACK.peak);
        }
        
        updateStickyBarAvailability();
        
        // Refresh calendar if it exists
        if (window.bookingCalendar) {
            window.bookingCalendar.renderCalendar();
        }
        
        return true;
    } catch (error) {
        console.error('Error fetching blocked dates:', error);
        
        // Set calendarLoaded to true anyway so calendar can render
        calendarLoaded = true;
        blockedDates = [];
        console.warn('⚠️ Calendar sync failed - showing all dates as available (fallback mode)');
        
        // Still try to fetch pricing
        await fetchPriceLabsPricing();
        updateStickyBarAvailability();
        
        return false;
    }
}

// Calculate total price for date range with smart pricing
function calculateTotalPrice(checkinDate, checkoutDate) {
    let subtotal = 0;
    const current = new Date(checkinDate);
    const end = new Date(checkoutDate);
    
    // Calculate total by summing each night's rate
    while (current < end) {
        const nightlyRate = getNightlyRate(current);
        subtotal += nightlyRate;
        current.setDate(current.getDate() + 1);
    }
    
    return {
        subtotal: subtotal,
        cleaningFee: CLEANING_FEE,
        total: subtotal + CLEANING_FEE,
        avgNightlyRate: Math.round(subtotal / Math.ceil((end - checkinDate) / (1000 * 60 * 60 * 24)))
    };
}

// Check if a date range overlaps with blocked dates
function isDateRangeBlocked(checkinDate, checkoutDate) {
    const current = new Date(checkinDate);
    const end = new Date(checkoutDate);
    
    // Check each day in the range
    while (current < end) {
        const dateStr = current.toISOString().split('T')[0];
        
        if (blockedDates.includes(dateStr)) {
            return true; // Date range includes a blocked date
        }
        
        current.setDate(current.getDate() + 1);
    }
    
    return false; // No conflicts
}

// Check Availability Function
async function checkAvailability(event) {
    // Get dates from custom calendar if available
    let checkinDate, checkoutDate;
    
    if (window.bookingCalendar) {
        const selectedDates = window.bookingCalendar.getSelectedDates();
        checkinDate = selectedDates.checkin;
        checkoutDate = selectedDates.checkout;
        
        if (!checkinDate || !checkoutDate) {
            alert('Please select both check-in and check-out dates on the calendar');
            return;
        }
    } else {
        // Fallback to old input method
        const checkin = document.getElementById('checkin-date')?.value;
        const checkout = document.getElementById('checkout-date')?.value;
        
        if (!checkin || !checkout) {
            alert('Please select both check-in and check-out dates');
            return;
        }
        
        checkinDate = new Date(checkin);
        checkoutDate = new Date(checkout);
    }
    
    const guests = document.getElementById('guest-count').value;
    const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
    
    if (nights < 1) {
        alert('Check-out must be after check-in');
        return;
    }
    
    // Show loading state
    const button = event?.target || document.querySelector('#booking-modal button');
    const originalText = button.textContent;
    button.textContent = 'Checking Availability...';
    button.disabled = true;
    
    // CHECK AVAILABILITY - Backend database check (authoritative)
    try {
        const checkinStr = formatDate(checkinDate);
        const checkoutStr = formatDate(checkoutDate);
        
        const response = await fetch(`/backend/api/availability.php?listing_id=1&start_date=${checkinStr}&end_date=${checkoutStr}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to check availability');
        }
        
        // Check if any unavailable dates in the range
        const unavailableDates = data.data.unavailable_dates || [];
        
        // Check each night from check-in to checkout-1 (checkout day doesn't count)
        const current = new Date(checkinDate);
        const end = new Date(checkoutDate);
        let hasConflict = false;
        
        while (current < end) {
            const dateStr = formatDate(current);
            if (unavailableDates.includes(dateStr)) {
                hasConflict = true;
                break;
            }
            current.setDate(current.getDate() + 1);
        }
        
        if (hasConflict) {
            alert('Sorry, these dates are not available. Please select different dates.');
            button.textContent = 'Check Availability';
            button.disabled = false;
            return;
        }
        
        // Check minimum nights requirement from PriceLabs
        let requiredMinNights = 0;
        let foundPriceLabsData = false;
        
        // Try PriceLabs first - it has dynamic minimum nights
        if (priceLabsLoaded && priceLabsData) {
            const current2 = new Date(checkinDate);
            const end2 = new Date(checkoutDate);
            
            // Find the strictest minimum nights requirement in the date range
            while (current2 < end2) {
                const dateStr = formatDate(current2);
                if (priceLabsData[dateStr]) {
                    foundPriceLabsData = true;
                    // PriceLabs uses 'minStay' field (can be 1, 2, 3, etc.)
                    const minStay = priceLabsData[dateStr].minStay || priceLabsData[dateStr].min_stay || 1;
                    requiredMinNights = Math.max(requiredMinNights, minStay);
                }
                current2.setDate(current2.getDate() + 1);
            }
            
            console.log(`PriceLabs minimum nights for this range: ${requiredMinNights}`);
        }
        
        // Only use fallback if PriceLabs data is completely missing
        if (!foundPriceLabsData) {
            requiredMinNights = 2; // Default minimum when no PriceLabs data
            console.log('⚠️  No PriceLabs data found - using default minimum nights: 2');
        }
        
        // Validate minimum nights (allow 1-night bookings if PriceLabs permits)
        if (requiredMinNights > 0 && nights < requiredMinNights) {
            alert(`These dates require a minimum of ${requiredMinNights} night${requiredMinNights > 1 ? 's' : ''}. You selected ${nights} night${nights > 1 ? 's' : ''}.\n\nThis may be due to other bookings around these dates. Please try different dates or extend your stay.`);
            button.textContent = 'Check Availability & Pricing';
            button.disabled = false;
            return;
        }
        
        console.log(`✓ Minimum nights check passed: ${nights} night(s) meets requirement of ${requiredMinNights}`);
    
        
    } catch (error) {
        console.error('Backend availability check failed:', error);
        console.log('Falling back to frontend calendar check');
        
        // Fallback to frontend check if backend fails
        if (isDateRangeBlocked(checkinDate, checkoutDate)) {
            alert('Sorry, these dates are not available. Please select different dates.');
            button.textContent = 'Check Availability';
            button.disabled = false;
            return;
        }
    }
    
    // DATES ARE AVAILABLE - Continue
    // Calculate pricing with dynamic rates from PriceLabs
    const pricing = calculateTotalPrice(checkinDate, checkoutDate);
    
    // Display pricing
    document.getElementById('nightly-rate').textContent = `$${pricing.avgNightlyRate}`;
    document.getElementById('num-nights').textContent = nights;
    document.getElementById('total-price').innerHTML = `<strong>$${pricing.total}</strong>`;
    document.getElementById('pricing-display').style.display = 'block';
    
    // Show dynamic pricing note if rates vary
    if (Object.keys(priceLabsData).length > 0) {
        console.log('Using dynamic PriceLabs pricing');
    }
    
    button.textContent = 'Continue to Checkout';
    button.disabled = false;
    button.onclick = function() {
        const checkinStr = formatDate(checkinDate);
        const checkoutStr = formatDate(checkoutDate);
        proceedToCheckout(checkinStr, checkoutStr, guests, pricing.avgNightlyRate, nights, pricing.total);
    };
}

// Proceed to Checkout - Direct Booking (NO Airbnb)
function proceedToCheckout(checkin, checkout, guests, nightlyRate, nights, total) {
    // Store booking details in sessionStorage for checkout page
    const bookingData = {
        checkin: checkin,
        checkout: checkout,
        guests: guests,
        nightlyRate: nightlyRate,
        nights: nights,
        subtotal: nightlyRate * nights,
        cleaningFee: CLEANING_FEE,
        total: total
    };
    
    sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
    
    console.log('Proceeding to direct checkout:', bookingData);
    
    // Redirect to YOUR checkout page (direct booking, no Airbnb)
    window.location.href = 'checkout.html';
}

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Create mobile menu toggle if it doesn't exist
    const header = document.getElementById('main-header');
    const nav = header ? header.querySelector('nav') : null;
    
    if (header && nav && !document.querySelector('.mobile-menu-toggle')) {
        const toggle = document.createElement('button');
        toggle.className = 'mobile-menu-toggle';
        toggle.innerHTML = '☰';
        toggle.setAttribute('aria-label', 'Toggle menu');
        
        // Insert toggle before nav
        nav.parentNode.insertBefore(toggle, nav);
        
        // Toggle menu on click
        toggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            this.innerHTML = nav.classList.contains('active') ? '✕' : '☰';
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!header.contains(e.target)) {
                nav.classList.remove('active');
                toggle.innerHTML = '☰';
            }
        });
        
        // Close menu when clicking a link
        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                nav.classList.remove('active');
                toggle.innerHTML = '☰';
            });
        });
    }
});

// Load gallery images from database (CMS)
async function loadGalleryImages() {
    const galleryGrid = document.querySelector('#intro .image-grid');
    
    // Only run on homepage with gallery
    if (!galleryGrid) {
        return;
    }
    
    try {
        const response = await fetch('/backend/api/gallery.php');
        
        if (!response.ok) {
            console.log('Gallery API unavailable, using static images');
            return;
        }
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            // Clear existing static images
            galleryGrid.innerHTML = '';
            
            // Populate with database images
            result.data.forEach(img => {
                const imgElement = document.createElement('img');
                imgElement.src = img.url;
                imgElement.alt = img.alt || img.fileName;
                if (img.caption) {
                    imgElement.title = img.caption;
                }
                galleryGrid.appendChild(imgElement);
            });
            
            console.log(`✓ Loaded ${result.data.length} images from gallery CMS`);
        } else {
            console.log('No active gallery images, using static fallback');
        }
    } catch (error) {
        console.log('Gallery API error, using static images:', error.message);
    }
}
