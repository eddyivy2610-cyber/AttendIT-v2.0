function normalizePhoneNumber(phone) {
    if (phone === null || phone === undefined) return '';
    return String(phone).replace(/\D/g, '');
}

module.exports = { normalizePhoneNumber };
