/**
 * Automatic Calendar Sync - Airbnb & PriceLabs Integration
 * Fetches blocked dates from iCal feeds and serves to frontend
 */

const express = require('express');
const cors = require('cors');
const axios = require('axios');
const NodeCache = require('node-cache');

// Initialize cache (30 minute expiry)
const cache = new NodeCache({ stdTTL: 1800 });

const app = express();
app.use(cors());
app.use(express.json());

// CONFIGURATION - UPDATE THESE WITH YOUR URLS
const config = {
    airbnbIcalUrl: process.env.AIRBNB_ICAL_URL || 'https://www.airbnb.com/calendar/ical/YOUR_LISTING_ID.ics',
    pricelabsIcalUrl: process.env.PRICELABS_ICAL_URL || '', // PriceLabs calendar URL (if separate)
    cacheKey: 'blockedDates',
    cacheDuration: 1800 // 30 minutes in seconds
};

/**
 * Parse iCal format and extract blocked dates
 */
function parseIcal(icalData) {
    const blockedDates = new Set();
    
    // Split into lines
    const lines = icalData.split(/\r?\n/);
    
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
            // Process the event
            if (eventStart && eventEnd) {
                const start = parseDateString(eventStart);
                const end = parseDateString(eventEnd);
                
                if (start && end) {
                    // Add all dates in the range
                    let current = new Date(start);
                    const endDate = new Date(end);
                    
                    while (current < endDate) {
                        blockedDates.add(formatDate(current));
                        current.setDate(current.getDate() + 1);
                    }
                }
            }
            inEvent = false;
        } else if (inEvent) {
            // Parse DTSTART and DTEND
            if (line.startsWith('DTSTART')) {
                eventStart = line.split(':')[1];
            } else if (line.startsWith('DTEND')) {
                eventEnd = line.split(':')[1];
            }
        }
    }
    
    return Array.from(blockedDates).sort();
}

/**
 * Parse iCal date string (handles both DATE and DATETIME formats)
 */
function parseDateString(dateStr) {
    // Remove VALUE=DATE: prefix if present
    dateStr = dateStr.replace(/;?VALUE=DATE:?/, '');
    
    // Format: YYYYMMDD or YYYYMMDDTHHMMSS
    const year = parseInt(dateStr.substring(0, 4));
    const month = parseInt(dateStr.substring(4, 6)) - 1; // JS months are 0-indexed
    const day = parseInt(dateStr.substring(6, 8));
    
    return new Date(year, month, day);
}

/**
 * Format date as YYYY-MM-DD
 */
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Fetch and parse iCal from URL
 */
async function fetchCalendar(url) {
    try {
        const response = await axios.get(url, {
            timeout: 10000,
            headers: {
                'User-Agent': 'Pure Michigan Getaways Calendar Sync/1.0'
            }
        });
        
        return parseIcal(response.data);
    } catch (error) {
        console.error(`Error fetching calendar from ${url}:`, error.message);
        return [];
    }
}

/**
 * Fetch all calendars and merge blocked dates
 */
async function getAllBlockedDates() {
    const blockedDatesSets = [];
    
    // Fetch Airbnb calendar
    if (config.airbnbIcalUrl && config.airbnbIcalUrl !== 'https://www.airbnb.com/calendar/ical/YOUR_LISTING_ID.ics') {
        console.log('Fetching Airbnb calendar...');
        const airbnbDates = await fetchCalendar(config.airbnbIcalUrl);
        blockedDatesSets.push(airbnbDates);
        console.log(`Found ${airbnbDates.length} blocked dates from Airbnb`);
    }
    
    // Fetch PriceLabs calendar (if different from Airbnb)
    if (config.pricelabsIcalUrl) {
        console.log('Fetching PriceLabs calendar...');
        const pricelabsDates = await fetchCalendar(config.pricelabsIcalUrl);
        blockedDatesSets.push(pricelabsDates);
        console.log(`Found ${pricelabsDates.length} blocked dates from PriceLabs`);
    }
    
    // Merge all dates (remove duplicates)
    const allDates = new Set();
    for (const dates of blockedDatesSets) {
        dates.forEach(date => allDates.add(date));
    }
    
    return Array.from(allDates).sort();
}

/**
 * API Endpoint: Get blocked dates
 */
app.get('/blocked-dates', async (req, res) => {
    try {
        // Check cache first
        const cachedDates = cache.get(config.cacheKey);
        
        if (cachedDates) {
            console.log('Returning cached blocked dates');
            return res.json({
                blockedDates: cachedDates,
                cached: true,
                lastUpdated: new Date(cache.getTtl(config.cacheKey) - (config.cacheDuration * 1000)).toISOString(),
                nextUpdate: new Date(cache.getTtl(config.cacheKey)).toISOString()
            });
        }
        
        // Fetch fresh data
        console.log('Fetching fresh calendar data...');
        const blockedDates = await getAllBlockedDates();
        
        // Cache the results
        cache.set(config.cacheKey, blockedDates);
        
        res.json({
            blockedDates: blockedDates,
            cached: false,
            lastUpdated: new Date().toISOString(),
            nextUpdate: new Date(Date.now() + (config.cacheDuration * 1000)).toISOString(),
            count: blockedDates.length
        });
        
    } catch (error) {
        console.error('Error in /blocked-dates endpoint:', error);
        res.status(500).json({
            error: 'Failed to fetch blocked dates',
            blockedDates: [] // Return empty array as fallback
        });
    }
});

/**
 * API Endpoint: Force refresh cache
 */
app.post('/refresh-calendar', async (req, res) => {
    try {
        console.log('Force refreshing calendar...');
        cache.del(config.cacheKey);
        
        const blockedDates = await getAllBlockedDates();
        cache.set(config.cacheKey, blockedDates);
        
        res.json({
            success: true,
            message: 'Calendar refreshed',
            blockedDates: blockedDates,
            count: blockedDates.length,
            lastUpdated: new Date().toISOString()
        });
    } catch (error) {
        console.error('Error refreshing calendar:', error);
        res.status(500).json({
            error: 'Failed to refresh calendar'
        });
    }
});

/**
 * API Endpoint: Health check
 */
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'calendar-sync',
        uptime: process.uptime(),
        airbnbConfigured: config.airbnbIcalUrl !== 'https://www.airbnb.com/calendar/ical/YOUR_LISTING_ID.ics',
        pricelabsConfigured: !!config.pricelabsIcalUrl
    });
});

// Start server (for local testing)
const PORT = process.env.PORT || 3001;

if (require.main === module) {
    app.listen(PORT, () => {
        console.log(`Calendar Sync API running on port ${PORT}`);
        console.log(`Airbnb iCal: ${config.airbnbIcalUrl !== 'https://www.airbnb.com/calendar/ical/YOUR_LISTING_ID.ics' ? 'Configured ✓' : 'Not configured ✗'}`);
        console.log(`PriceLabs iCal: ${config.pricelabsIcalUrl ? 'Configured ✓' : 'Not configured ✗'}`);
    });
}

// Export for serverless (Vercel)
module.exports = app;
