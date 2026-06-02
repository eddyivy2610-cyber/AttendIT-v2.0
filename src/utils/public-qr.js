const crypto = require('crypto');
const QRCode = require('qrcode');

const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

function getBaseUrl(baseUrl) {
    return String(baseUrl || '').replace(/\/$/, '');
}

function getRegisterToken() {
    return process.env.PUBLIC_REGISTER_TOKEN || process.env.PUBLIC_STUDENT_FORM_TOKEN || 'change-me-public-register-token';
}

function attendanceTokenFor(weekday) {
    const secret = process.env.PUBLIC_ATTENDANCE_SECRET || 'change-me-public-attendance-secret';
    return crypto.createHash('sha256').update(`${secret}:${weekday}`).digest('hex').slice(0, 16);
}

function buildRegisterUrl(baseUrl) {
    return `${getBaseUrl(baseUrl)}/register/${getRegisterToken()}`;
}

function buildAttendanceUrl(baseUrl, weekday) {
    return `${getBaseUrl(baseUrl)}/attendance/${weekday}/${attendanceTokenFor(weekday)}`;
}

function buildPublicQrItems(baseUrl) {
    const registerUrl = buildRegisterUrl(baseUrl);
    const attendanceLinks = WEEKDAYS.map(weekday => ({
        weekday,
        label: weekday.charAt(0).toUpperCase() + weekday.slice(1),
        url: buildAttendanceUrl(baseUrl, weekday)
    }));

    return [
        {
            key: 'register',
            label: 'Student Register',
            url: registerUrl
        },
        ...attendanceLinks.map(link => ({
            key: link.weekday,
            label: `${link.label} Attendance`,
            url: link.url,
            weekday: link.weekday
        }))
    ];
}

async function addQrDataUrls(items) {
    return Promise.all(items.map(async item => ({
        ...item,
        qrDataUrl: await QRCode.toDataURL(item.url, {
            errorCorrectionLevel: 'M',
            margin: 1,
            width: 320
        })
    })));
}

async function buildPublicQrAssets(baseUrl) {
    const items = await addQrDataUrls(buildPublicQrItems(baseUrl));
    const registerItem = items.find(item => item.key === 'register');
    return {
        registerUrl: registerItem?.url || buildRegisterUrl(baseUrl),
        registerQrDataUrl: registerItem?.qrDataUrl || null,
        publicAttendanceLinks: items.filter(item => item.key !== 'register')
    };
}

module.exports = {
    WEEKDAYS,
    attendanceTokenFor,
    buildRegisterUrl,
    buildAttendanceUrl,
    buildPublicQrItems,
    buildPublicQrAssets
};
