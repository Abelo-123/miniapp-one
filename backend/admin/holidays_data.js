// Holiday Calendar Data - Major Holidays 2025-2026
const MAJOR_HOLIDAYS = {
    "2025": [
        { date: "2025-01-01", name: "New Year's Day", type: "international" },
        { date: "2025-01-07", name: "Ethiopian Christmas (Genna)", type: "ethiopian" },
        { date: "2025-01-19", name: "Ethiopian Epiphany (Timkat)", type: "ethiopian" },
        { date: "2025-02-14", name: "Valentine's Day", type: "international" },
        { date: "2025-03-02", name: "Adwa Victory Day", type: "ethiopian" },
        { date: "2025-03-30", name: "Ramadan Begins", type: "islamic" },
        { date: "2025-04-18", name: "Good Friday", type: "christian" },
        { date: "2025-04-20", name: "Easter Sunday", type: "christian" },
        { date: "2025-04-27", name: "Ethiopian Easter (Fasika)", type: "ethiopian" },
        { date: "2025-04-29", name: "Eid al-Fitr", type: "islamic" },
        { date: "2025-05-01", name: "International Workers' Day", type: "international" },
        { date: "2025-05-05", name: "Patriots' Victory Day", type: "ethiopian" },
        { date: "2025-05-28", name: "Derg Downfall Day", type: "ethiopian" },
        { date: "2025-07-06", name: "Eid al-Adha", type: "islamic" },
        { date: "2025-09-11", name: "Ethiopian New Year (Enkutatash)", type: "ethiopian" },
        { date: "2025-09-27", name: "Meskel (Finding of True Cross)", type: "ethiopian" },
        { date: "2025-10-31", name: "Halloween", type: "international" },
        { date: "2025-11-27", name: "Thanksgiving (US)", type: "international" },
        { date: "2025-12-25", name: "Christmas Day", type: "international" },
        { date: "2025-12-26", name: "Boxing Day", type: "international" },
        { date: "2025-12-31", name: "New Year's Eve", type: "international" }
    ],
    "2026": [
        { date: "2026-01-01", name: "New Year's Day", type: "international" },
        { date: "2026-01-07", name: "Ethiopian Christmas (Genna)", type: "ethiopian" },
        { date: "2026-01-19", name: "Ethiopian Epiphany (Timkat)", type: "ethiopian" },
        { date: "2026-02-14", name: "Valentine's Day", type: "international" },
        { date: "2026-03-02", name: "Adwa Victory Day", type: "ethiopian" },
        { date: "2026-03-19", name: "Ramadan Begins", type: "islamic" },
        { date: "2026-04-03", name: "Good Friday", type: "christian" },
        { date: "2026-04-05", name: "Easter Sunday", type: "christian" },
        { date: "2026-04-12", name: "Ethiopian Easter (Fasika)", type: "ethiopian" },
        { date: "2026-04-18", name: "Eid al-Fitr", type: "islamic" },
        { date: "2026-05-01", name: "International Workers' Day", type: "international" },
        { date: "2026-05-05", name: "Patriots' Victory Day", type: "ethiopian" },
        { date: "2026-05-28", name: "Derg Downfall Day", type: "ethiopian" },
        { date: "2026-06-25", name: "Eid al-Adha", type: "islamic" },
        { date: "2026-09-11", name: "Ethiopian New Year (Enkutatash)", type: "ethiopian" },
        { date: "2026-09-27", name: "Meskel (Finding of True Cross)", type: "ethiopian" },
        { date: "2026-10-31", name: "Halloween", type: "international" },
        { date: "2026-11-26", name: "Thanksgiving (US)", type: "international" },
        { date: "2026-12-25", name: "Christmas Day", type: "international" },
        { date: "2026-12-26", name: "Boxing Day", type: "international" },
        { date: "2026-12-31", name: "New Year's Eve", type: "international" }
    ]
};

// Shopping/Sales Holidays
const SHOPPING_HOLIDAYS = {
    "2025": [
        { date: "2025-01-01", name: "New Year Sale", type: "shopping" },
        { date: "2025-02-14", name: "Valentine's Day Sale", type: "shopping" },
        { date: "2025-03-08", name: "Women's Day Sale", type: "shopping" },
        { date: "2025-07-04", name: "Independence Day Sale", type: "shopping" },
        { date: "2025-07-16", name: "Amazon Prime Day", type: "shopping" },
        { date: "2025-11-28", name: "Black Friday", type: "shopping" },
        { date: "2025-12-01", name: "Cyber Monday", type: "shopping" },
        { date: "2025-12-12", name: "12.12 Sale", type: "shopping" },
        { date: "2025-12-24", name: "Christmas Eve Sale", type: "shopping" },
        { date: "2025-12-26", name: "Boxing Day Sale", type: "shopping" },
    ],
    "2026": [
        { date: "2026-01-01", name: "New Year Sale", type: "shopping" },
        { date: "2026-02-14", name: "Valentine's Day Sale", type: "shopping" },
        { date: "2026-03-08", name: "Women's Day Sale", type: "shopping" },
        { date: "2026-07-04", name: "Independence Day Sale", type: "shopping" },
        { date: "2026-07-21", name: "Amazon Prime Day", type: "shopping" },
        { date: "2026-11-27", name: "Black Friday", type: "shopping" },
        { date: "2026-11-30", name: "Cyber Monday", type: "shopping" },
        { date: "2026-12-12", name: "12.12 Sale", type: "shopping" },
        { date: "2026-12-24", name: "Christmas Eve Sale", type: "shopping" },
        { date: "2026-12-26", name: "Boxing Day Sale", type: "shopping" }
    ]
};

function getAllHolidays(year) {
    const major = MAJOR_HOLIDAYS[year] || [];
    const shopping = SHOPPING_HOLIDAYS[year] || [];
    return [...major, ...shopping].sort((a, b) => new Date(a.date) - new Date(b.date));
}

function getHolidaysByType(year, type) {
    return getAllHolidays(year).filter(h => h.type === type);
}

function getUpcomingHolidays(limit = 10) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const currentYear = today.getFullYear();
    const nextYear = currentYear + 1;

    const allHolidays = [...getAllHolidays(currentYear), ...getAllHolidays(nextYear)];

    return allHolidays
        .filter(h => new Date(h.date) >= today)
        .slice(0, limit);
}
